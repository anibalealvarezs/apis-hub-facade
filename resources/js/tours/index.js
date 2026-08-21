import { tourManager } from './tour-manager';
import { dataSourcesTour } from './flows/data-sources-tour';
import { projectSettingsTour } from './flows/project-settings-tour';
import { telemetryTour } from './flows/telemetry-tour';
import { dataExplorerTour } from './flows/data-explorer-tour';
import { assetGroupsTour } from './flows/asset-groups-tour';
import { dashboardBuilderTour } from './flows/dashboard-builder-tour';
import { alertsTour } from './flows/alerts-tour';
import { customKpisTour } from './flows/custom-kpis-tour';
import { derivedMetricsTour } from './flows/derived-metrics-tour';

// Register All Modular Flows
tourManager.register('data-sources', dataSourcesTour);
tourManager.register('project-settings', projectSettingsTour);
tourManager.register('telemetry', telemetryTour);
tourManager.register('data-explorer', dataExplorerTour);
tourManager.register('asset-groups', assetGroupsTour);
tourManager.register('dashboards', dashboardBuilderTour);
tourManager.register('alerts', alertsTour);
tourManager.register('custom-kpis', customKpisTour);
tourManager.register('derived-metrics', derivedMetricsTour);

// Export for global access & initialize
export function initOnboardingTours() {
    window.apisHubTours = tourManager;
    tourManager.init();
}
