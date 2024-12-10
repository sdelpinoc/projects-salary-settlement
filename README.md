# Formulario de Liquidación

## Para levantar formulario

- Instalar docker desktop

- Crear un archivo .env y agregar las credenciales de la base de datos, basándose en el archivo .env.example

- Ejecutar: `docker compose up` o `docker compose up -d`

- Ejecutar el seed de la base de datos una vez: <http://127.0.0.1:81/db/seed-db.php?access=seed>

- Acceder al formulario: <http://127.0.0.1:81/>

- Para revisar la base de datos, ir a phpMyAdmin: <http://localhost:8081/>, en servidor usar el valor de: "db"
- Para el resto de datos, usar los valores de MYSQL_USER y MYSQL_PASSWORD del archivo de .env

- Los números de los puertos pueden cambiar, dependiendo de los establecidos en el documento docker-compose.yaml

## Observaciones

- Respecto al valor de la UF, se utiliza el valor del último día del mes, de no encontrar resultados para ese día se
utiliza el valor del día actual.

- Los valores de la UF son obtenidos de <https://mindicador.cl/>
