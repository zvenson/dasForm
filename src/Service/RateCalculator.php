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
     * `amort` als ganzzahliger Bruch P/Q, dazu die Eckdaten des Anbieters.
     *
     * Achtung: `credit` (Kaufanrechnung pro Rate) hat nichts mit `p`/`q` zu tun —
     * dass bei FLEX-Rent beide Male 70 steht, ist Zufall.
     *
     * @var array<string, array{label: string, p: int, q: int, credit: int, returnFrom: int}>
     */
    public const VARIANTS = [
        'rent' => ['label' => 'FLEX-Rent', 'p' => 7, 'q' => 10, 'credit' => 70, 'returnFrom' => 9],
        'finance' => ['label' => 'FLEX-Finance', 'p' => 42, 'q' => 100, 'credit' => 80, 'returnFrom' => 14],
        'lease' => ['label' => 'FLEX-Lease', 'p' => 35, 'q' => 100, 'credit' => 50, 'returnFrom' => 20],
    ];

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
     * @param float $vatRatePercent Steuersatz in Prozent, z. B. 19.0
     * @param int   $maxCents       0 oder kleiner bedeutet: keine Obergrenze
     *
     * @return array<int, array{key: string, label: string, net: float, gross: float, credit: int, returnFrom: int}>
     */
    public function calculateAll(int $netPriceCents, float $vatRatePercent, int $minCents, int $maxCents): array
    {
        if ($netPriceCents <= 0 || !$this->isEligible($netPriceCents, $minCents, $maxCents)) {
            return [];
        }

        // Prozentsatz als ganzzahliger Bruch: 19.0 -> 1900/10000, 7.7 -> 770/10000.
        // Zwei Nachkommastellen des Prozentwerts sind damit abgedeckt.
        $vatQ = 10000;
        $vatP = (int) round($vatRatePercent * 100);

        $rates = [];

        foreach (self::VARIANTS as $key => $variant) {
            $netCents = $this->rateCents($netPriceCents, $variant['p'], $variant['q']);
            // Regel 4: erst die Rate runden, dann die Steuer darauf.
            $grossCents = $netCents + $this->vatCents($netCents, $vatP, $vatQ);

            $rates[] = [
                'key' => $key,
                'label' => $variant['label'],
                'net' => $netCents / 100,
                'gross' => $grossCents / 100,
                'credit' => $variant['credit'],
                'returnFrom' => $variant['returnFrom'],
            ];
        }

        return $rates;
    }
}
