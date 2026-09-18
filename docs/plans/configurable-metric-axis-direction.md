# Plan de Implementación: Dirección de Eje Configurable por Métrica en Dashboard Builder

## 1. Contexto y Objetivos
Actualmente, la inversión de escala en los ejes (0% o valor mínimo arriba, valores mayores hacia abajo) se aplica de forma automática por convención para métricas como `bounce_rate` o `position` en gráficos de dispersión, líneas, barras y combo.

El usuario ha solicitado que la **dirección del eje (Normal vs. Invertido)** sea **configurable por el usuario**, integrada de forma contextual **dentro de la configuración de la métrica** en cada serie activa del Dashboard Builder, evitando mostrar listas globales de métricas no asociadas al widget o canales inactivos.

---

## 2. Decisiones de Diseño y UX

### Ubicación en la Interfaz (UI)
Se integrará directamente en el **Modal de Configuración y Personalización de Métrica** (`showMetricNamingModal`):
- Actualmente, al hacer clic en el botón de etiqueta (tag/nombre personalizado) de una métrica activa en una serie, se abre una ventana modal con:
  - Vista previa de la leyenda.
  - Nombre personalizado de la métrica.
  - Opciones de formato de leyenda (mostrar canal, desglose, unidad).
- **Nuevo Bloque: Dirección del Eje (Axis Direction)**:
  - Ofrecerá 3 opciones con selector o botones segmentados claros:
    1. **Auto (Por defecto)**: Aplica la convención natural de la métrica (ej. `bounce_rate` y `position` invertidos; `clicks`, `sessions`, etc. normales).
    2. **Normal**: Eje estándar (el valor 0 o mínimo abajo, valores crecientes hacia arriba).
    3. **Invertido**: Eje inverso (el valor mínimo o 0% arriba, valores crecientes hacia abajo).
- **Indicador visual**: Si la métrica tiene una dirección configurada diferente a `auto`, el botón de la métrica o tooltip indicará que tiene personalización de eje activa.

---

## 3. Cambios Propuestos por Componente

### A. Vista del Constructor (Frontend Blade)
- **[MODIFY]** [`resources/views/filament/app/pages/dashboard-builder.blade.php`](file:///d:/laragon/www/apis-hub-facade/resources/views/filament/app/pages/dashboard-builder.blade.php):
  - Añadir sección "Dirección del Eje / Axis Scale" dentro de `showMetricNamingModal`.
  - Control Alpine.js vinculado a `namingModalForm.axis_direction` (`'auto' | 'normal' | 'inverted'`).
  - Breve texto explicativo para el usuario ("Auto aplica orden descendente para tasas de rebote o rankings, Normal inicia en 0 abajo, Invertido sitúa 0 o el mínimo arriba").

### B. Lógica del Constructor (Frontend JS)
- **[MODIFY]** [`resources/js/dashboards/dashboard-builder.js`](file:///d:/laragon/www/apis-hub-facade/resources/js/dashboards/dashboard-builder.js):
  - `openMetricNamingModal(seriesIndex, metricKey)`:
    - Cargar `axis_direction: current.axis_direction || 'auto'` en `namingModalForm`.
  - `saveMetricNamingModal()`:
    - Persistir `axis_direction` dentro de `series.metric_namings[metricKey]`.
  - `hasCustomMetricNaming()`:
    - Considerar custom si `axis_direction && axis_direction !== 'auto'`.
  - `getWidgetControlsSignature()` y `confirmWidgetControls()`:
    - Serializar `axis_direction` dentro de `series_metric_namings` y `raw_series` para que se guarde en base de datos al confirmar el widget.

### C. Renderizado de Gráficos (Dashboard Renderer)
- **[MODIFY]** [`public/js/dashboard-renderer.js`](file:///d:/laragon/www/apis-hub-facade/public/js/dashboard-renderer.js):
  - Crear helper `getMetricAxisDirection(metricKey, seriesIdx, controls)`:
    - Comprueba si en `controls.series_metric_namings[seriesIdx][metricKey].axis_direction` (o `raw_series`) hay un valor explícito:
      - Si `'inverted'` -> Retorna `true` (`reverse: true`, `beginAtZero: false`).
      - Si `'normal'` -> Retorna `false` (`reverse: false`, `beginAtZero: true`).
      - Si `'auto'` o no definido -> Aplica la detección estándar automática (`isBounceRate || isPosition`).
  - Aplicar a:
    - `_renderLineChart`: Escalas `y` (única o multieje `y_${idx}`).
    - `_renderBarChart`: Escalas `y`.
    - `_renderComboChart`: Determinación de `leftAxisReverse` y `rightAxisReverse` según las métricas asignadas a cada eje.
    - `_renderScatterPlot`: Eje Y (`controls.metrics[0]`) y Eje X (`controls.metrics[1]`).

### D. Backend Controller
- **[MODIFY]** [`app/Http/Controllers/Api/DashboardWidgetDataController.php`](file:///d:/laragon/www/apis-hub-facade/app/Http/Controllers/Api/DashboardWidgetDataController.php):
  - En la normalización de scatter plot y charts:
    - Leer `axis_direction` de los controles resueltos (`resolvedControls['raw_series']` o `resolvedControls['series_metric_namings']`).
    - Propagar el flag `reverse_y` acorde (`true` para inverted, `false` para normal, fallback auto).

---

## 4. Plan de Verificación

1. **Dashboard Builder UI**:
   - Abrir el widget de SEO técnico (Clicks & Bounce Rate).
   - Abrir la personalización de la métrica `bounce_rate`: verificar que aparece el selector de dirección con valor `Auto`.
   - Cambiar a `Normal`: guardar y verificar en la vista previa del widget que el eje de rebote ahora empieza en 0% abajo.
   - Cambiar a `Invertido`: guardar y verificar que el eje se invierte con 0% arriba.
2. **Compatibilidad con otras métricas**:
   - Probar con `clicks` o `impressions` cambiándolo a `Invertido` para validar que cualquier métrica responde adecuadamente si el usuario lo desea.
3. **Persistencia**:
   - Guardar el widget / dashboard, recargar la página y confirmar que la preferencia persiste.
