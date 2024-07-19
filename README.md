# Formulario de Liquidación

## Para levantar formulario:

- Instalar docker desktop

- Crear un archivo .env y agregar las credenciales de la base de datos, basándose en .env.example

- Ejecutar: `docker compose up`

- Ejecutar el seed de la base de datos una vez: http://127.0.0.1/scripts/seed-db.php?access=seed

- Acceder al formulario: http://127.0.0.1/

- Para revisar la base de datos, ir a phpMyAdmin: http://localhost:8080/, en servidor usar el valor de: "db"
- Usar las credenciales de MYSQL_USER y MYSQL_PASSWORD