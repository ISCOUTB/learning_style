# Estilo de Aprendizaje ALPY

![GitHub release (latest by date)](https://img.shields.io/github/v/release/ISCOUTB/learning_style)
![GitHub Workflow Status](https://img.shields.io/github/actions/workflow/status/ISCOUTB/learning_style/release.yml)
![Moodle compatibility](https://img.shields.io/badge/Moodle-2.5%2B-blue)
![PHP compatibility](https://img.shields.io/badge/PHP-7.4%2B-blue)
![License](https://img.shields.io/github/license/ISCOUTB/learning_style)

Para la implementación del módulo de identificación de estilos de aprendizaje en el sistema ALPY, se integró la prueba estándar que permiten personalizar el aprendizaje de los estudiantes. El módulo de estilo de aprendizaje está basado en la prueba de Felder y Soloman (FSLSM), que evalúa el estilo predominante de los estudiantes en cuatro dimensiones clave.

Esta prueba, con 11 preguntas por dimensión, permite al sistema calcular un valor predominante, lo que facilita la adaptación de los recursos de aprendizaje. Siguiendo las evaluaciones del experto pedagógico, los recursos asignados a los estudiantes se categorizan según su idoneidad para cada estilo, asignando valores que determinan si un recurso es indiferente, bueno o esencial.

## 🚀 Releases Automáticos

Este proyecto utiliza GitHub Actions para crear releases automáticos. Cada vez que se crea un nuevo tag, se genera automáticamente:
- **Paquete ZIP** listo para instalar en Moodle
- **Release en GitHub** con notas de cambios detalladas
- **Documentación** actualizada de la versión

### 📦 Descargar Última Versión
➡️ **[Descargar desde GitHub Releases](https://github.com/ISCOUTB/learning_style/releases/latest)**

Para más información sobre el sistema de releases, consulta [RELEASES.md](RELEASES.md).

# Prerrequisitos de instalación
Este bloque es compatible con Moodle versión 4.2 o superior.

# Guía de instalación
1. Ve a la carpeta `/blocks`.
2. Clona el repositorio o descomprime el .zip
3. Cambia el nombre de la carpeta descomprimida a `learning_style`.
4. Inicia sesión como administrador en el servidor donde esté corriendo Moodle.
5. Instala el bloque.
6. Agrega el bloque al curso en que desees saber los estilos de aprendizaje de los estudiantes.
7. Configura el bloque.
8. Bloque instalado!

# Escenarios de uso.
1. Los estudiantes matriculados realizan la encuesta de estilos de aprendizaje.
2. Al finalizar la encuesta, los estudiantes visualizan el panel de resultados individual.
3. El administrador/docente visualiza el dashboard de estilos de aprendizaje.

# Galería.
## Encuesta.
![image](https://github.com/user-attachments/assets/05c8d0e0-1bc6-44b3-99fd-da8e20b97772)
## Panel de resultados (alumno) y Recomendaciones por eje de estilo de aprendizaje..
![image](https://github.com/user-attachments/assets/df064614-e0af-4b4c-918e-e27998f06c02)
![image](https://github.com/user-attachments/assets/c7558e27-47a4-4066-a618-2388b49ef742)
## Dashboard del administrador/docente y recomendaciones del estudio.
![image](https://github.com/user-attachments/assets/285bea57-55a3-4a2e-b525-e6c08d99b00b)
![image](https://github.com/user-attachments/assets/c5d4ddb5-8f58-452a-a5cf-4934de1b3512)






