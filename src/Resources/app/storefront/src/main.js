import DasFormContactInject from './das-form/das-form';

const PluginManager = window.PluginManager;
PluginManager.register('DasFormContactInject', DasFormContactInject, '[data-dasform-contact-inject]');
