# Plan de Implementación Detallado: Sistema de Upselling Visual y Restricción de Features por Tier

## 1. Resumen y Decisiones de Diseño Acordadas

1. **Interacción mediante Modal Informativo (Acordado):**
   Al interactuar con un elemento restringido (botón con corona, input desactivado con trigger, o banner de upgrade), se desplegará un modal contextual explicativo. Este modal ofrecerá al usuario:
   - **Opción A (Actualizar):** Redirección directa al checkout/gestor de suscripciones en el panel de cuenta preseleccionando el perfil actual (`/account/subscription?profile={id}`).
   - **Opción B (Reasignar):** Si el usuario dispone de otro perfil con el tier adecuado ya contratado en su cuenta, se le orienta a reasignar el perfil del proyecto.

2. **Diferenciación de Roles (Caveat 1 - Acordado):**
   - **Si el usuario actual ES el propietario del Billing Profile:** El modal muestra el botón principal *"Actualizar este perfil a [Tier]"*.
   - **Si el usuario actual NO es el propietario del Billing Profile (ej. colaborador o agencia administrando proyecto con tarjeta de cliente):** El modal no ofrece el botón de pago directo, sino el mensaje explicativo:
     > *"Esta funcionalidad requiere el plan [Tier]. Solicita al titular del perfil de facturación (:owner_name) que actualice la suscripción o vincula uno de tus propios perfiles de pago al proyecto."*

3. **Estrategia de Ejecución Iterativa (Uno por Uno):**
   No se implementará todo de golpe. Se abordará elemento por elemento, permitiendo validar individualmente su funcionamiento, sus tests automatizados y su checklist manual.

---

## 2. Inventario Completo de Elementos a Implementar (Fase a Fase)

A continuación se detalla cada elemento individual, su tipo de componente UI, el criterio de visualización/bloqueo y el plan de verificación:

---

### Elemento 1: Toggle de Dashboards Públicos (`is_public`)
- **Ubicación:** `DashboardResource` (Formulario de creación/edición de Dashboard).
- **Tipo de Elemento UI:** `Toggle` de formulario Filament con `Badge` de Corona contiguo.
- **Tier Mínimo:** `PRO` (En Free no se permiten dashboards públicos).
- **Comportamiento en UI:**
  - Si el proyecto está en `FREE`:
    - El toggle permanece visible pero en estado `disabled(true)`.
    - Etiqueta: `Público (accesible por cualquier colaborador y enlace compartido)` + Badge de Corona `👑 Pro`.
    - Helper text con botón/enlace de trigger para abrir el modal explicativo de upgrade.
  - Si el proyecto está en `PRO / ULTRA / ENTERPRISE`:
    - Toggle completamente editable y funcional (sujeto únicamente al límite numérico de dashboards públicos de su tier).
- **Criterio de Implementación:**
  - Modificar el campo `is_public` en `DashboardResource::form()`.
  - Crear acción de modal en el formulario o enlace accesible.

---

### Elemento 2: Configuración de TypeSafe AI en Preferencias del Proyecto
- **Ubicación:** `ProjectSettings` (Modal de edición de preferencias del proyecto).
- **Tipo de Elemento UI:** `TextInput` (campo de API Key) + Banner / Callout de estado.
- **Tier Mínimo:** `PRO` (o `FREE` si la feature flag temporal `enable_free_ai_classification` está encendida en Admin).
- **Comportamiento en UI:**
  - Si el proyecto corre APIs Hub `< 1.16.0`:
    - Muestra alerta técnica de actualización de release (`Upgrade Required: APIs Hub v1.16.0+`). No muestra corona ni CTA de pago porque es un bloqueo de infraestructura.
  - Si el proyecto corre APIs Hub `>= 1.16.0` pero el tier es `FREE` (y feature flag temporal apagada):
    - La sección `AI Semantic Classification (TypeSafe)` permanece **visible**.
    - El campo `TextInput::make('typesafe_api_key')` se muestra `disabled(true)`.
    - Placeholder: *"Requiere Plan Pro o promoción temporal"*.
    - Banner superior estilizado con borde ámbar/dorado, ícono de corona y badge `👑 Pro Feature`.
    - Botón en el banner: *"Desbloquear Clasificación IA"* que abre el modal de conversión.
  - Si el tier es `PRO` o superior:
    - Campo habilitado con su comportamiento normal y badge de cobertura.

---

### Elemento 3: Botón de Contexto Global de IA (`Global AI Context`)
- **Ubicación:** `DataSources` (Header action en la pestaña Google Search Console).
- **Tipo de Elemento UI:** `Action Button` en cabecera de página.
- **Tier Mínimo:** `PRO` (o `FREE` con promo temporal activa).
- **Comportamiento en UI:**
  - Anteriormente: Se ocultaba con `->visible(...)`.
  - Nueva implementación:
    - Siempre visible si el usuario tiene permiso `manage_channels` y el release soporta AI (`>= 1.16.0`).
    - Si el proyecto es `FREE` sin promo:
      - Botón renderizado en color ámbar/dorado con ícono `heroicon-m-sparkles` / corona y badge pequeño `Pro`.
      - Al hacer clic, **no** abre el formulario modal de configuración de contexto, sino el **Modal de Upgrade a Pro**.
    - Si el proyecto es compatible:
      - Abre el modal habitual de configuración de marca, descripción y competidores globales.

---

### Elemento 4: Botón de Contexto Específico de IA por Activo (`Asset AI Context`)
- **Ubicación:** `DataSources` (Columna de acciones por fila en la tabla de propiedades de Google Search Console).
- **Tipo de Elemento UI:** `Action Button` en fila de tabla.
- **Tier Mínimo:** `PRO` (o `FREE` con promo temporal activa).
- **Comportamiento en UI:**
  - Anteriormente: Se ocultaba con `if ($this->activeChannel === 'google_search_console' && ... supportsAiClassification())`.
  - Nueva implementación:
    - Visible para todos los proyectos con versión `>= 1.16.0`.
    - Si el tier es `FREE` sin promo:
      - Botón estilizado con distintivo de corona `👑`.
      - Al hacer clic abre el modal explicativo: *"Configura contexto específico de marca para :website desbloqueando el Plan Pro"*.
    - Si el tier es compatible:
      - Abre el modal de configuración de negocio del activo específico.

---

### Elemento 5: Límite de Creación de Dashboards Privados
- **Ubicación:** `DashboardResource` (Cabecera del listado de Dashboards).
- **Tipo de Elemento UI:** `Header Action` ("Nuevo Dashboard") + Callout / Banner de cuota.
- **Tier Mínimo:** Escalonado: Free (1), Pro (5), Ultra/Founder (15), Enterprise (Ilimitado).
- **Comportamiento en UI:**
  - Actualmente: El botón desaparece al alcanzar el límite (`canCreate` retorna `false`).
  - Nueva implementación:
    - El botón "Nuevo Dashboard" permanece visible.
    - Si el proyecto alcanzó la cuota de su tier (ej. 1 de 1 en Free):
      - El botón muestra un badge de corona (ej. `👑 Aumentar Límite`).
      - Al pulsar el botón, en lugar de bloquear silenciosamente, despliega el modal: *"Has alcanzado el límite de 1 dashboard de tu plan Free. Pasa a Pro para crear hasta 5 dashboards, o a Ultra para 15."* con el CTA de upgrade.

---

### Elemento 6: Límite de Creación de Custom KPIs
- **Ubicación:** `CustomKpiResource` (Cabecera del listado de KPIs).
- **Tipo de Elemento UI:** `Header Action` ("Crear KPI") + Indicador de cuota en cabecera.
- **Tier Mínimo:** Escalonado: Free (10), Pro (30), Ultra (50), Enterprise (Ilimitado).
- **Comportamiento en UI:**
  - Similar a Dashboards: Si el proyecto alcanza los 10 KPIs en Free o 30 en Pro, el botón no desaparece; abre el modal de upselling indicando cuántos KPIs adicionales obtendrá con el siguiente tier.

---

### Elemento 7: Límite de Creación de Métricas Derivadas
- **Ubicación:** `DerivedMetricResource` (Cabecera del listado de Métricas Derivadas).
- **Tipo de Elemento UI:** `Header Action` ("Crear Métrica Derivada").
- **Tier Mínimo:** Escalonado: Free (10), Pro (30), Ultra (50), Enterprise (Ilimitado).
- **Comportamiento en UI:**
  - Al llegar al tope, el botón ofrece la opción de ampliar la cuota al siguiente tier mediante el modal.

---

### Elemento 8: Invitación de Colaboradores al Proyecto
- **Ubicación:** `ManageCollaborators` (Equipo y Colaboradores).
- **Tipo de Elemento UI:** Cabecera de tabla / Botón `Invitar Colaborador` + Banner explicativo.
- **Tier Mínimo:** `ULTRA` (Los planes Free y Pro son de uso individual sin colaboradores adicionales).
- **Comportamiento en UI:**
  - En Free y Pro:
    - El botón de invitar muestra el badge de corona `👑 ULTRA` e invoca el modal de upgrade con explicación de planes y enlace a la suscripción para el propietario.
    - Se muestra el banner explicativo contextual con enlaces a la suscripción.
  - En Ultra / Enterprise:
    - El botón despliega el formulario regular para enviar la invitación por email con rol y scopes.

---

## 3. Plan de Tests Automatizados de Cobertura

Para asegurar que ninguna restricción se rompa ni visual ni programáticamente, se crearán tests exhaustivos con **Pest / PHPUnit**:

### 3.1. Tests Unitarios (`tests/Unit/FeatureGateTest.php`)
- Comprobar que `FeatureGateService` (o `BillingLifecycleService`) responde con el tier mínimo correcto para cada feature.
- Comprobar que evalúa adecuadamente el tier del billing profile vinculado al proyecto.
- Comprobar el comportamiento cuando el proyecto no tiene billing profile asignado (fallback seguro a Free/Suspended).
- Comprobar la interacción con la feature flag temporal de AI classification para Free.

### 3.2. Tests de Integración en Filament (`tests/Feature/TierUpsellingUiTest.php`)
- **Test Toggle `is_public` en Dashboards:**
  - Verificar que un usuario autenticado en proyecto `FREE` ve el campo `is_public` como `disabled`.
  - Verificar que en proyecto `PRO` el campo está activo y puede ser guardado como `true`.
  - Verificar que si un usuario en `FREE` envía `is_public = true` mediante manipulación de payload Livewire, el backend lo rechaza o lo sanea.
- **Test Acciones de AI en DataSources:**
  - Verificar que en proyecto `FREE` (con promo apagada), las acciones `configureAiContext` y `configureAssetAiContext` abren el modal de upgrade o impiden la ejecución de la mutación.
  - Verificar que en proyecto `PRO` abren el formulario de contexto de negocio.
- **Test Cuotas de Creación:**
  - Verificar que un proyecto Free con 1 dashboard no puede crear un segundo dashboard, y la acción devuelve la respuesta de upgrade esperada.
  - Verificar que un proyecto Pro con 1 dashboard puede crear más hasta llegar a 5.
- **Test Roles y Titularidad del Billing Profile en Modal:**
  - Verificar que cuando `auth()->id() === $profile->user_id`, el modal contiene la URL directa de suscripción.
  - Verificar que cuando `auth()->id() !== $profile->user_id`, el modal muestra el texto de aviso al titular sin enlace de compra directa.

---

## 4. Guía / Checklist de Comprobación Manual por Tier

Esta lista servirá de guía paso a paso para verificar la experiencia visual y funcional en cada plan:

```markdown
### 📋 CHECKLIST MANUAL DE REVISIÓN POR TIER

#### 🟡 CUENTA / PROYECTO EN TIER FREE (Sin Promoción Temporal)
- [ ] 1. **DataSources > Google Search Console:**
      - [ ] El botón "Global AI Context" tiene ícono/badge de corona.
      - [ ] Al pulsar "Global AI Context", se abre el Modal de Upgrade (no el formulario).
      - [ ] En la tabla de activos de GSC, el botón "AI Context" tiene corona y abre el Modal de Upgrade.
- [ ] 2. **Project Settings > AI Classification:**
      - [ ] Si tenant >= v1.16.0, la sección está visible.
      - [ ] El campo "TypeSafe API Key" está deshabilitado / bloqueado.
      - [ ] Hay un banner con corona invitando al plan Pro o explicando la restricción.
- [ ] 3. **Dashboards > Crear/Editar Dashboard:**
      - [ ] El toggle "Público" está desactivado/deshabilitado.
      - [ ] Muestra el badge "👑 Pro" junto a la etiqueta.
      - [ ] El texto de ayuda enlaza al modal de upgrade.
- [ ] 4. **Dashboards > Listado (Límites):**
      - [ ] Al tener 1 dashboard creado, el botón o callout indica que se alcanzó el límite de Free (1/1).
      - [ ] Al intentar crear otro, se muestra el modal para pasar a Pro (hasta 5).
- [ ] 5. **Custom KPIs > Listado (Límites):**
      - [ ] Al tener 10 KPIs, se muestra la invitación de upgrade para desbloquear 30 (Pro) o 50 (Ultra).
- [ ] 6. **Equipo y Colaboradores (`ManageCollaborators`):**
      - [ ] Muestra el banner contextual de advertencia indicando que la colaboración requiere plan Ultra o Enterprise.
      - [ ] El botón de la cabecera "Invitar Colaborador" muestra el distintivo de corona `👑 ULTRA`.
      - [ ] Al pulsar "Invitar Colaborador", despliega el modal explicativo de upgrade con CTA directo o mensaje al titular.
- [ ] 7. **Modal de Upgrade - Verificación de Roles:**
      - [ ] Si soy dueño del billing profile: veo botón directo "Mejorar este perfil".
      - [ ] Si NO soy dueño del billing profile: veo el mensaje dirigido al titular (:owner_name).

---

#### 🟢 CUENTA / PROYECTO EN TIER FREE (Con Promoción Temporal AI Activa)
- [ ] 1. En Admin > Features, activar switch "Enable AI-assisted keywords classification for free accounts".
- [ ] 2. En el proyecto Free:
      - [ ] El botón "Global AI Context" ahora abre el formulario normal.
      - [ ] El campo "TypeSafe API Key" en Project Settings ahora es editable.
      - [ ] Los dashboards públicos y colaboradores continúan bloqueados con corona (el switch solo afecta a AI).

---

#### 🔵 CUENTA / PROYECTO EN TIER PRO
- [ ] 1. **DataSources > Google Search Console:**
      - [ ] Botón "Global AI Context" es completamente funcional (sin corona de bloqueo).
      - [ ] Botones "AI Context" de activos son funcionales.
- [ ] 2. **Project Settings > AI Classification:**
      - [ ] Campo TypeSafe editable y funcional.
- [ ] 3. **Dashboards:**
      - [ ] Toggle "Público" está habilitado (editable).
      - [ ] Permite crear hasta 5 dashboards (tanto públicos como privados).
      - [ ] Al llegar al 5to dashboard, el botón sugiere pasar a Ultra (hasta 15).
- [ ] 4. **Custom KPIs:**
      - [ ] Permite crear hasta 30 KPIs.
- [ ] 5. **Equipo y Colaboradores (`ManageCollaborators`):**
      - [ ] Muestra el botón con corona `👑 ULTRA` invitando a mejorar al plan Ultra (o Enterprise) para habilitar miembros de equipo.

---

#### 🟣 CUENTA / PROYECTO EN TIER ULTRA / FOUNDER
- [ ] 1. **Dashboards:** Permite crear hasta 15 dashboards públicos y privados.
- [ ] 2. **Custom KPIs:** Permite crear hasta 50 KPIs.
- [ ] 3. **API Access:** Acceso a credenciales API y rate limits avanzados.

---

#### 🟠 CUENTA / PROYECTO EN TIER ENTERPRISE
- [ ] 1. **Límites:** KPIs, Métricas y Dashboards sin límite numérico (ilimitados).
- [ ] 2. **Billing Profiles:** Posibilidad de compartir el perfil de facturación con otros usuarios del equipo.
```

---

## 5. Orden de Ejecución Sugerido (Paso a Paso)

Para avanzar con la máxima seguridad y control:

1. **Paso 1:** Implementar el servicio base de verificación de capacidades (`FeatureGateService` / `BillingLifecycleService`) y el componente visual reutilizable del Modal de Upgrade con detección de titularidad.
2. **Paso 2:** Implementar el primer elemento: **Toggle de Dashboards Públicos (`is_public`)** en `DashboardResource`, junto con sus tests automatizados. Lo revisamos juntos y validamos.
3. **Paso 3:** Implementar el segundo elemento: **TypeSafe AI en `ProjectSettings`** con sus tests. Validamos.
4. **Paso 4:** Implementar el tercer y cuarto elemento: **Acciones de AI en `DataSources` (Global y por Activo)** con sus tests. Validamos.
5. **Paso 5:** Implementar los elementos restantes de límites de cuota (Dashboards, KPIs, Colaboradores).
