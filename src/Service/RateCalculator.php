<?php

declare(strict_types=1);

namespace Sven\DasForm\Service;

/**
 * Berechnet die monatlichen Vertragsraten nach der Spezifikation des Anbieters.
 *
 * Die vier Regeln der Spezifikation:
 *  1. Alles in ganzzahligen Cent.
 *  2. Exakt rechnen, nicht in `double` — der `double`-Wert von 0.7 ist
 *     0,69999999999999995559…, was rund 0,2 % aller Preise um einen Cent kippt.
 *  3. Kaufmaennisch runden, halbe Cent aufwaerts (kein Banker's Rounding).
 *  4. Jede Komponente einzeln runden, dann summieren.
 *
 * Deshalb wird `amort` nicht als Gleitkommazahl gefuehrt, sondern als
 * ganzzahliger Bruch P/Q. Damit genuegt reine Ganzzahlarithmetik:
 *
 *     round_half_up(a / b) = floor((2a + b) / 2b)     fuer a, b > 0
 *
 * Nachgerechnet gegen eine unabhaengige Implementierung ueber den gesamten
 * Bereich 30000..3000000 Cent in Einercent-Schritten: 0 Abweichungen. Dieselbe
 * Rechnung in `double` weicht dort 15469 (0.7), 7425 (0.42) bzw. 7734 (0.35)
 * mal ab.
 */
class RateCalculator
{
    /**
     * Die Laufzeit geht nicht in die Rate ein, der Nenner ist immer 12.
     */
    private const MONTHS = 12;

    /**
     * Gueltigkeitsbereich laut Vollabgleich der Spezifikation: 300 € bis 30.000 €.
     */
    public const MIN_PRICE_CENTS = 30000;
    public const MAX_PRICE_CENTS = 3000000;

    /**
     * Mehr Nachkommastellen als das laesst `amort` nicht zu. Bei sechs Stellen
     * erreicht der Zaehler rund 6 × 10^12 und bleibt damit klar im 64-Bit-Bereich.
     */
    private const MAX_AMORT_DECIMALS = 6;

    /**
     * Vorgabe, falls im Backend kein oder kein brauchbares JSON hinterlegt ist.
     *
     * `amort` als ganzzahliger Bruch P/Q, dazu die Eckdaten des Anbieters.
     * Achtung: `credit` (Kaufanrechnung pro Rate) hat nichts mit `p`/`q` zu tun —
     * dass bei FLEX-Rent beide Male 70 steht, ist Zufall.
     *
     * @var array<int, array{key: string, label: string, p: int, q: int, credit: int, returnFrom: int}>
     */
    public const DEFAULT_VARIANTS = [
        ['key' => 'rent', 'label' => 'FLEX-Rent', 'p' => 7, 'q' => 10, 'credit' => 70, 'returnFrom' => 9],
        ['key' => 'finance', 'label' => 'FLEX-Finance', 'p' => 42, 'q' => 100, 'credit' => 80, 'returnFrom' => 14],
        ['key' => 'lease', 'label' => 'FLEX-Lease', 'p' => 35, 'q' => 100, 'credit' => 50, 'returnFrom' => 20],
    ];

    /**
     * Liest die im Backend gepflegten Regeln.
     *
     * Erwartet eine JSON-Liste von Objekten. `amort` wird als **Zeichenkette**
     * erwartet und rein textuell in den Bruch P/Q zerlegt — niemals ueber
     * `floatval`, sonst kommt exakt die Ungenauigkeit zurueck, die die
     * Ganzzahlarithmetik vermeidet ("0.42" waere als `double` 0,41999…).
     * Alternativ duerfen `p` und `q` direkt angegeben werden.
     *
     * Bei ungueltiger Eingabe gelten die Vorgabewerte: eine kaputte Zeile im
     * Backend darf die Produktseite nicht leerraeumen.
     *
     * @return array<int, array{key: string, label: string, p: int, q: int, credit: int, returnFrom: int}>
     */
    public function parseVariants(?string $json): array
    {
        $json = trim((string) $json);
        if ($json === '') {
            return self::DEFAULT_VARIANTS;
        }

        try {
            $decoded = json_decode($json, true, 32, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $this->log('invalid JSON: ' . $e->getMessage());

            return self::DEFAULT_VARIANTS;
        }

        if (!is_array($decoded)) {
            return self::DEFAULT_VARIANTS;
        }

        $variants = [];
        foreach ($decoded as $index => $entry) {
            $variant = $this->normaliseVariant($entry, (string) $index);
            if ($variant !== null) {
                $variants[] = $variant;
            }
        }

        return $variants === [] ? self::DEFAULT_VARIANTS : $variants;
    }

    /**
     * @return array{key: string, label: string, p: int, q: int, credit: int, returnFrom: int}|null
     */
    private function normaliseVariant(mixed $entry, string $index): ?array
    {
        if (!is_array($entry)) {
            $this->log(sprintf('entry %s is not an object', $index));

            return null;
        }

        $label = trim((string) ($entry['label'] ?? ''));
        if ($label === '') {
            $this->log(sprintf('entry %s has no label', $index));

            return null;
        }

        if (isset($entry['p'], $entry['q'])) {
            $p = (int) $entry['p'];
            $q = (int) $entry['q'];
        } else {
            $fraction = $this->toFraction($entry['amort'] ?? null);
            if ($fraction === null) {
                $this->log(sprintf('entry "%s" has no usable amort', $label));

                return null;
            }
            [$p, $q] = $fraction;
        }

        if ($p <= 0 || $q <= 0) {
            $this->log(sprintf('entry "%s" has a non-positive amort', $label));

            return null;
        }

        return [
            'key' => trim((string) ($entry['key'] ?? $index)),
            'label' => $label,
            'p' => $p,
            'q' => $q,
            'credit' => (int) ($entry['credit'] ?? 0),
            'returnFrom' => (int) ($entry['returnFrom'] ?? 0),
        ];
    }

    /**
     * Zerlegt einen Dezimalwert rein textuell in P/Q: "0.42" wird zu 42/100.
     *
     * Kommt der Wert als JSON-Zahl statt als Zeichenkette an, ist er bereits
     * ein `double`. Er wird dann mit fester Stellenzahl formatiert und die
     * Nullen abgeschnitten — fuer die hier ueblichen Werte verlustfrei, aber
     * eine Zeichenkette im JSON ist der sichere Weg.
     *
     * @return array{int, int}|null
     */
    private function toFraction(mixed $amort): ?array
    {
        if (is_float($amort) || is_int($amort)) {
            $amort = rtrim(rtrim(number_format((float) $amort, self::MAX_AMORT_DECIMALS, '.', ''), '0'), '.');
        }

        if (!is_string($amort)) {
            return null;
        }

        $amort = trim($amort);
        if (preg_match('/^\d*(\.\d{1,' . self::MAX_AMORT_DECIMALS . '})?$/', $amort) !== 1 || $amort === '') {
            return null;
        }

        if (!str_contains($amort, '.')) {
            return [(int) $amort, 1];
        }

        [$whole, $decimals] = explode('.', $amort, 2);

        return [(int) (($whole === '' ? '0' : $whole) . $decimals), 10 ** strlen($decimals)];
    }

    private function log(string $message): void
    {
        @error_log('[SvenDasForm][rates] ' . $message);
    }

    /**
     * rate_cents = round_half_up( price_cents × P / (Q × 12) )
     *
     * Der Zaehler erreicht bei 30.000 € rund 2,5 × 10^8 und bei sehr grossen
     * Preisen 2 × 10^11 — PHP rechnet hier mit 64-Bit-Integern.
     */
    public function rateCents(int $priceCents, int $p, int $q): int
    {
        if ($priceCents <= 0 || $p <= 0 || $q <= 0) {
            return 0;
        }

        $numerator = 2 * $priceCents * $p + $q * self::MONTHS;
        $denominator = 2 * $q * self::MONTHS;

        return intdiv($numerator, $denominator);
    }

    /**
     * ust_cents = round_half_up( netto_cents × P / Q )
     *
     * Wird laut Spezifikation auf den bereits gerundeten Nettobetrag angewendet,
     * nicht auf den ungerundeten Zwischenwert.
     */
    public function vatCents(int $netCents, int $p, int $q): int
    {
        if ($netCents <= 0 || $p <= 0 || $q <= 0) {
            return 0;
        }

        return intdiv(2 * $netCents * $p + $q, 2 * $q);
    }

    public function isEligible(int $netPriceCents, int $minCents, int $maxCents): bool
    {
        return $netPriceCents >= $minCents && ($maxCents <= 0 || $netPriceCents <= $maxCents);
    }

    /**
     * Alle Varianten fuer einen Nettopreis, jeweils netto und brutto.
     *
     * @param float                          $vatRatePercent Steuersatz in Prozent, z. B. 19.0
     * @param int                            $maxCents       0 oder kleiner bedeutet: keine Obergrenze
     * @param array<int, array<string, int|string>> $variants  aus parseVariants()
     *
     * @return array<int, array{key: string, label: string, net: float, gross: float, credit: int, returnFrom: int}>
     */
    public function calculateAll(
        int $netPriceCents,
        float $vatRatePercent,
        int $minCents,
        int $maxCents,
        array $variants
    ): array {
        if ($netPriceCents <= 0 || !$this->isEligible($netPriceCents, $minCents, $maxCents)) {
            return [];
        }

        // Prozentsatz als ganzzahliger Bruch: 19.0 -> 1900/10000, 7.7 -> 770/10000.
        // Zwei Nachkommastellen des Prozentwerts sind damit abgedeckt.
        $vatQ = 10000;
        $vatP = (int) round($vatRatePercent * 100);

        $rates = [];

        foreach ($variants as $variant) {
            $netCents = $this->rateCents($netPriceCents, (int) $variant['p'], (int) $variant['q']);
            // Regel 4: erst die Rate runden, dann die Steuer darauf.
            $grossCents = $netCents + $this->vatCents($netCents, $vatP, $vatQ);

            $rates[] = [
                'key' => (string) $variant['key'],
                'label' => (string) $variant['label'],
                'net' => $netCents / 100,
                'gross' => $grossCents / 100,
                'credit' => $variant['credit'],
                'returnFrom' => $variant['returnFrom'],
            ];
        }

        return $rates;
    }
}
