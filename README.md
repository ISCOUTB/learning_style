# Bloque Exploración de Estilos de Aprendizaje (Moodle)

El bloque **Exploración de Estilos de Aprendizaje** permite a estudiantes realizar un test tipo *Index of Learning Styles (Felder–Soloman)* y obtener un perfil en cuatro dimensiones: **Activo/Reflexivo**, **Sensorial/Intuitivo**, **Visual/Verbal** y **Secuencial/Global**. Para docentes y administradores, incorpora vistas de seguimiento, métricas agregadas, panel analítico y exportación.

Este repositorio incluye:
- Experiencia de estudiante con **guardado automático**, validaciones y reanudación.
- Herramientas docentes con **panel de administración**, **vista individual**, **dashboard de métricas** y **exportación CSV**.

## Contenido

- [Funcionalidades](#funcionalidades)
- [Recorrido Visual](#recorrido-visual)
- [Sección técnica (modelo de datos, cálculo, flujos, permisos, endpoints)](#sección-técnica)
- [Instalación](#instalación)
- [Operación y soporte](#operación-y-soporte)
- [Contribuciones](#contribuciones)
- [Equipo de desarrollo](#equipo-de-desarrollo)

---

## Funcionalidades

### Para estudiantes
- **Aplicación del test** (44 ítems, opciones A/B) distribuido en 4 páginas.
- **Guardado progresivo** (autosave) y **continuación** desde el punto exacto donde se dejó.
- **Validación por página** antes de avanzar o finalizar.
- **Resultados** con barras de predominancia por dimensión y **gráfico radar** (0–11).
- **Recomendaciones** consultables desde las etiquetas destacadas (popover).

### Para docentes / administradores
- **Dashboard embebido** con métricas del curso (porcentaje encuestado, estilo(s) dominante(s) y menos dominante(s), y gráficos).
- **Panel de administración** con:
  - **Conteos** (matriculados, completados, en progreso, tasa de finalización).
  -  **Estadísticas Generales** (Top 4 de Estilos de Aprendizaje más comunes y Promedios por dimensión).
  - **Tabla de participantes** (nombre, correo, estado, perfil de aprendizaje).
  - Acceso a **vista individual** por estudiante.
  - Posibilidad de **eliminación** de resultados individuales.
  - **Descarga CSV** de resultados completados.
- Opción para **mostrar/ocultar** las descripciones en el bloque principal **(oculto por defecto)**.
- **Controles de privacidad**: acceso restringido por capacidades y por matrícula en el curso.

---
## Recorrido Visual

### 1. Experiencia del Estudiante

**Acceso Intuitivo y Llamado a la Acción**

El recorrido comienza con una invitación clara y directa. Desde el bloque principal del curso, el estudiante puede visualizar su estado actual y acceder al test con un solo click, facilitando la participación sin fricciones.
<p align="center">
  <img src="https://github.com/user-attachments/assets/3b25dfef-7d84-4fbd-9f55-e3fe08735397" alt="Invitación al Test" width="528">
</p>

**Interfaz de Evaluación Optimizada**

Se presenta un entorno de respuesta limpio y libre de distracciones. La interfaz ha sido diseñada para priorizar la legibilidad y la facilidad de uso, permitiendo que el estudiante se concentre totalmente en el proceso de autodescubrimiento.
<p align="center">
  <img src="https://github.com/user-attachments/assets/0eaabfda-483e-41f9-b23a-34ae8b6f7083" alt="Formulario del Test" width="528">
</p>

**Asistencia y Validación en Tiempo Real**

Para garantizar la integridad de los datos, el sistema implementa una validación inteligente. Si el usuario olvida alguna respuesta, el sistema lo guía visualmente mediante alertas en rojo y un desplazamiento automático hacia los campos pendientes, asegurando una experiencia sin errores.

<p align="center">
  <img src="https://github.com/user-attachments/assets/85357609-47bf-451d-8161-04d94455c3c5" alt="Validación" width="528">
</p>

**Persistencia de Progreso y Continuidad**

Entendemos que el tiempo es valioso. Si el estudiante debe interrumpir su sesión, el sistema guarda automáticamente su avance. Al regresar, el bloque muestra el porcentaje de progreso y permite reanudar el test exactamente donde se dejó, resaltando visualmente la siguiente pregunta a responder.
	
<p align="center">
  <img src="https://github.com/user-attachments/assets/4e234873-70ed-41c7-a3b1-5a219c78bb24" alt="Progreso del Test" height="350">
  &nbsp;&nbsp;
  <img src="https://github.com/user-attachments/assets/e53ca651-9442-42d5-b6e8-aa40e5e9ef3f" alt="Continuar Test" height="350">
</p>

**Confirmación de Envío Pendiente**
Si el estudiante ha completado las 44 preguntas pero aún no ha procesado el envío, el bloque muestra una notificación clara y amigable, invitándolo a formalizar la entrega y conocer su perfil de aprendizaje.

<p align="center">
  <img src="https://github.com/user-attachments/assets/a87de948-abe0-409a-a346-e60fcc1dac9f" alt="Confirmación de Test Completado" width="528">
</p>

**Análisis de Perfil y Recomendaciones Personalizadas**

Al finalizar, el estudiante recibe un resumen el bloque principal de sus estilos de aprendizaje predominantes, junto con un acceso directo a sus resultados detallados donde podrá ver gráficos y recomendaciones específicas.

<p align="center">
  <img src="https://github.com/user-attachments/assets/2991a026-675f-41cb-942d-741ad2270dbd" alt="Resultados del Estudiante" width="528">
</p>

<p align="center">
  <img src="https://github.com/user-attachments/assets/43c4217b-50e7-476b-a2b0-65281406bec9" alt="Vista Detallada del Estudiante" width="600">
</p>

### 2. Experiencia del Profesor

**Dashboard de Control Rápido (Vista del Bloque)**

El profesor cuenta con una vista ejecutiva desde el bloque, donde puede monitorizar métricas clave y gráficos de tendencia de forma inmediata, además de acceder a funciones avanzadas de exportación y administración.

<p align="center">
  <img src="https://github.com/user-attachments/assets/e60a4ab9-265d-473d-aa5b-0185afbc3dd8" alt="Bloque del Profesor" width="528">
</p>

**Centro de Gestión y Analíticas**

Un panel de administración que centraliza el seguimiento grupal. Permite visualizar quiénes han completado el proceso, quiénes están en curso y gestionar los resultados colectivos para adaptar la estrategia pedagógica del aula.

<p align="center">
  <img src="https://github.com/user-attachments/assets/5b826521-a4f5-4b12-98b4-d5e701dc2041" alt="Panel de Administración" width="800">
</p>

**Seguimiento Individualizado y Detallado**

El docente puede profundizar en el perfil específico de cada estudiante. Esta vista permite comprender las necesidades particulares de cada alumno y las recomendaciones sugeridas por el sistema para brindar un apoyo docente más humano y dirigido.

- **Nota:** Esta vista es la misma que la del estudiante, pero accesible por el profesor para cualquier alumno del curso.
---

## Sección técnica

Esta sección describe el comportamiento **tal como está implementado** en el bloque (cálculo, persistencia y endpoints).

### 1) Estructura del test y codificación de respuestas

- Total de preguntas: **44**.
- Opciones por pregunta: **A** y **B**.
- Persistencia en base de datos:
  - Se almacena una columna por pregunta: `q1` … `q44`.
  - Valores: **A = 0** y **B = 1**.
- Paginación: **11 preguntas por página** (4 páginas).

### 2) Mapeo de preguntas a dimensiones

Cada dimensión se calcula sobre **11 ítems** (por lo que cada par suma 11):

- **Activo / Reflexivo**: 1, 5, 9, 13, 17, 21, 25, 29, 33, 37, 41
- **Sensorial / Intuitivo**: 2, 6, 10, 14, 18, 22, 26, 30, 34, 38, 42
- **Visual / Verbal**: 3, 7, 11, 15, 19, 23, 27, 31, 35, 39, 43
- **Secuencial / Global**: 4, 8, 12, 16, 20, 24, 28, 32, 36, 40, 44

Interpretación de la codificación:

- En cada dimensión, **A (0)** incrementa la opción “izquierda” del par (Activo, Sensorial, Visual, Secuencial).
- En cada dimensión, **B (1)** incrementa la opción “derecha” del par (Reflexivo, Intuitivo, Verbal, Global).

### 3) Cálculo de puntajes y resultados

Para cada dimensión se guardan **dos tipos de salidas**:

1) **Conteos por estilo** (0–11), guardados en columnas numéricas:

- `ap_active` y `ap_reflexivo`
- `ap_sensorial` y `ap_intuitivo`
- `ap_visual` y `ap_verbal`
- `ap_secuencial` y `ap_global`

Cada par cumple:

- `ap_izquierda + ap_derecha = 11`

2) **Resultado compacto** por dimensión (texto), guardado en:

- `act_ref`, `sen_int`, `vis_vrb`, `seq_glo`

Formato del resultado: `"<diferencia><lado>"` donde:

- `<diferencia>` es el valor absoluto de la diferencia entre conteos.
- `<lado>` es `a` si predomina el lado izquierdo del par, o `b` si predomina el lado derecho.

Ejemplo (Activo/Reflexivo):

- Si `ap_active = 8` y `ap_reflexivo = 3`, entonces `act_ref = "5a"` (predomina Activo).
- Si `ap_active = 4` y `ap_reflexivo = 7`, entonces `act_ref = "3b"` (predomina Reflexivo).

> Nota: el bloque utiliza los conteos (`ap_*`) para visualizaciones y estadísticas; el resultado compacto (`*_ref`, etc.) sirve como representación resumida.

### 4) Guardado progresivo y reanudación

El flujo del estudiante está diseñado para soportar progreso parcial:

- Se crea/actualiza un registro con `is_completed = 0` mientras el test está en curso.
- El guardado automático se activa en cambios de respuesta y se ejecuta **400 milisegundos** después de la última interacción.
- El guardado progresivo envía un `POST` con `action=autosave` y `sesskey`, y actualiza únicamente las respuestas presentes en la página actual.

Reglas de integridad implementadas:

- **No se puede finalizar** si falta alguna respuesta: el servidor valida las 44 preguntas y redirige a la página donde está la primera pendiente.
- **No se puede saltar páginas**: el servidor limita el acceso a la “máxima página permitida” según lo contestado en el registro guardado.

### 5) Modelo de datos (tabla principal)

Tabla: `learning_style`

- `user` (índice **único**): el test se almacena **globalmente por usuario**.
- `is_completed`: 0 (en progreso) / 1 (completado).
- `q1..q44`: respuestas individuales.
- `ap_*`: conteos por dimensión.
- `act_ref`, `sen_int`, `vis_vrb`, `seq_glo`: resultado compacto por dimensión.
- `created_at`, `updated_at`: trazabilidad temporal.

Implicación importante:

- Al ser **único por usuario**, el resultado se comparte entre cursos. Las vistas docentes del curso muestran resultados de estudiantes matriculados aunque el test haya sido completado en otro curso.

### 6) Vistas, endpoints y exportación

**Estudiante**

- Formulario del test: `view.php?cid=<courseid>`

**Docente / Administrador**

- Panel de administración del curso: `admin_view.php?cid=<courseid>` (o `courseid=<courseid>`)
- Vista individual: `view_individual.php?courseid=<courseid>&userid=<userid>`
- Exportación CSV (solo completados): `download_results.php?courseid=<courseid>&sesskey=<sesskey>`

**Dashboard (métricas JSON)**

- Endpoint JSON: `dashboard/metrics.php?courseid=<courseid>&sesskey=<sesskey>`
- Contenido típico:
  - `total_students_on_course`: matriculados con capacidad de tomar test
  - `total_students`: matriculados con test completado
  - `data`: distribución por estilos (`num_act`, `num_ref`, `num_vis`, etc.)
  - `dominant_keys` / `least_dominant_keys`: claves con máximos/mínimos (soporta empates)
  - `dominance_is_flat`: indica si todos los estilos tienen el mismo conteo

**Notas de seguridad en exportación**

- La descarga CSV aplica sanitización para reducir el riesgo de *CSV formula injection* (prefija con `'` si el contenido inicia con `=`, `+`, `-` o `@`).

### 7) Permisos (capabilities)

El bloque define capacidades específicas:

- `block/learning_style:take_test` (estudiante): permite tomar el test.
- `block/learning_style:viewreports` (docente/manager): permite ver reportes, paneles, métricas y exportación.
- `block/learning_style:addinstance` / `block/learning_style:myaddinstance`: gestión de instancias del bloque.

Privacidad y alcance:

- Vistas docentes requieren `viewreports` (o permisos equivalentes) y, cuando aplica, restringen acceso a usuarios **matriculados** en el curso.

---

## Instalación

1. Descargar el plugin desde las *releases* del repositorio oficial: https://github.com/ISCOUTB/learning_style
2. En Moodle (como administrador):
   - Ir a **Administración del sitio → Extensiones → Instalar plugins**.
   - Subir el archivo ZIP.
   - Completar el asistente de instalación.
3. En un curso, agregar el bloque **Exploración de Estilos de Aprendizaje** desde el selector de bloques.

---

## Operación y soporte

### Consideraciones de despliegue

- Compatibilidad declarada: Moodle **4.0+**.
- El dashboard y las visualizaciones usan `core/chartjs` (Chart.js provisto por Moodle).
- Los popovers usan el módulo `theme_boost/popover` (depende del tema base de Moodle y su Bootstrap).

### Resolución de problemas (rápido)

- **El estudiante no ve el test**: validar que tenga la capacidad `block/learning_style:take_test` en el contexto del curso.
- **El docente no ve reportes**: validar `block/learning_style:viewreports`.
- **El dashboard no carga**: revisar que el navegador permita `fetch` con credenciales y que el `sesskey` sea válido.

---

## Contribuciones
¡Las contribuciones son bienvenidas! Si deseas mejorar este bloque, por favor sigue estos pasos:
1. Haz un fork del repositorio.
2. Crea una nueva rama para tu característica o corrección de errores.
3. Realiza tus cambios y asegúrate de que todo funcione correctamente.
4. Envía un pull request describiendo tus cambios.

---
## Equipo de desarrollo
- Jairo Enrique Serrano Castañeda
- Yuranis Henriquez Núñez
- Isaac David Sánchez Sánchez
- Santiago Andrés Orejuela Cueter
- María Valentina Serna González

<div align="center">
<strong>Con ❤️ para la Universidad Tecnológica de Bolívar</strong>
</div>
