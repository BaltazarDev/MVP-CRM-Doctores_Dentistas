# Guía de Despliegue en Coolify

Tu proyecto ha sido preparado para desplegarse fácilmente en Coolify usando Docker.

## Archivos Creados/Modificados

1.  **Dockerfile**: Ubicado en la raíz, construye una imagen única que contiene tanto el Backend (Node.js) como el Frontend (Archivos estáticos).
2.  **docker-compose.yml**: Define dos servicios: `app` (tu aplicación) y `db` (base de datos MySQL).
3.  **backend/server.js**: Modificado para servir automáticamente la carpeta `frontend`.
4.  **frontend/index.html**: Actualizado para comunicarse con el backend usando rutas relativas (funciona en local y producción).

## Pasos para Desplegar en Coolify

### Opción 1: Docker Compose (Recomendada)

1.  Sube tu código a un repositorio Git (GitHub/GitLab).
2.  En Coolify, crea un nuevo recurso y selecciona **Git Repository**.
3.  Selecciona tu repositorio y rama.
4.  Coolify detectará el `docker-compose.yml` automáticamente.
5.  **Configuración de Variables de Entorno (Environment Variables)**:
    *   Coolify leerá las del docker-compose, pero asegúrate de revisar:
    *   `MYSQL_ROOT_PASSWORD`: Cambia esto por una contraseña segura.
    *   `DB_PASSWORD`: Debe coincidir con `MYSQL_ROOT_PASSWORD`.
    *   `CLIENT_ID` y `CLIENT_SECRET`: Tus credenciales de Google Co.
    *   `REDIRECT_URI`: Cambia esto a `https://<TU_DOMINIO_EN_COOLIFY>/auth/google/callback`.

### Opción 2: Dockerfile (Solo App)

Si prefieres usar una base de datos gestionada externa u otro servicio MySQL en Coolify:

1.  Selecciona **Dockerfile** como tipo de despliegue.
2.  Define las variables de entorno manualmente en Coolify:
    *   `DB_HOST`: Host de tu base de datos.
    *   `DB_USER`: Usuario (ej. root).
    *   `DB_PASSWORD`: Contraseña.
    *   `DB_NAME`: crm_doctor.

## Notas Importantes

*   **Google Calendar**: Para que funcione en producción, debes agregar la URL de tu aplicación en la consola de Google Cloud (`Authorized redirect URIs`) coincidiendo con la variable `REDIRECT_URI`.
*   **Base de Datos**: El archivo `backend/setup.sql` se ejecuta automáticamente al iniciar la base de datos por primera vez para crear las tablas y datos necesarios.
