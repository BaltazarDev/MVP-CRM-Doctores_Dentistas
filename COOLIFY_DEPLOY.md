# Guía de Despliegue en Coolify

Tu proyecto ha sido preparado para desplegarse fácilmente en Coolify usando Docker.

## Archivos Creados/Modificados

1.  **Dockerfile**: Ubicado en la raíz, construye una imagen única que contiene tanto el Backend (Node.js) como el Frontend (Archivos estáticos).
2.  **docker-compose.yml**: Define dos servicios: `app` (tu aplicación) y `db` (base de datos MySQL).
3.  **backend/server.js**: Modificado para servir automáticamente la carpeta `frontend`.
4.  **frontend/index.html**: Actualizado para comunicarse con el backend usando rutas relativas (funciona en local y producción).

## Opción A: Despliegue Automático (Recomendado para Principiantes)

Esta opción crea **automáticamente** tanto la Aplicación como la Base de Datos y las conecta por ti.

1.  En Coolify, crea un nuevo recurso.
2.  Selecciona **"Docker Compose"** (o "Stack").
3.  Si te pide fuente, elige tu repositorio Git.
4.  Coolify leerá el archivo `docker-compose.yml`. verás que detecta 2 servicios: `app` y `db`.
5.  Dale a Deploy. ¡Listo!

*Nota: Si usas esta opción, Coolify se encarga de todo. No necesitas crear la base de datos manualmente.*

## Opción B: Despliegue Manual (Si ya deployaste con Dockerfile)

Si al crear el recurso elegiste "Application" -> "Dockerfile", entonces Coolify **SOLO** creó la aplicación de Node.js, pero **NO** la base de datos. Por eso te funciona en local (donde tienes docker-compose) pero no en Coolify.

**Solución:**

1.  En tu proyecto de Coolify, haz clic en **"+ New"** -> **"Database"** -> **"MySQL"**.
2.  Dale un nombre y créala.
3.  Entra a la base de datos creada y **copia** sus credenciales (Host, User, Password, Database Name).
    *   *Tip: El "Host" interno suele ser algo como `uuid-de-la-db` o `host.docker.internal` dependiendo de tu red.*
4.  Ve a tu Aplicación (la que ya deployaste) -> **"Configuration"** -> **"Environment Variables"**.
5.  Agrega las variables con los datos que copiaste:
    *   `DB_HOST`
    *   `DB_USER`
    *   `DB_PASSWORD`
    *   `DB_NAME`
6.  Guardar y **Redeploy**.

Al reiniciar, verás en los logs: *"Attempting to connect to database..."* y luego *"Database initialized successfully"*.

## Notas Importantes

*   **Google Calendar**: Para que funcione en producción, debes agregar la URL de tu aplicación en la consola de Google Cloud (`Authorized redirect URIs`) coincidiendo con la variable `REDIRECT_URI`.
*   **Base de Datos**: El archivo `backend/setup.sql` se ejecuta automáticamente al iniciar la base de datos por primera vez para crear las tablas y datos necesarios.
