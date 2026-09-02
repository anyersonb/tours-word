# Lote 2 — Motor limpio y contrato de datos

Laravel 12.69.1 + Filament 4.12.8 + spatie/laravel-translatable 6.11. El
contrato de datos completo (esquema, i18n, moneda, zona horaria, slugs/301,
módulos previstos) está en [`00-contrato-datos.md`](./00-contrato-datos.md).

## Cómo levantar el proyecto en local

```bash
# PHP 8.2 por ruta absoluta — el del PATH es 8.1 y Laravel 12 exige 8.2+
PHP=/g/laragon/bin/php/php8.2.1/php.exe

# Bases de datos (root sin contraseña)
/g/laragon/bin/mysql/mysql-8.0.30-winx64/bin/mysql.exe -uroot -e \
  "CREATE DATABASE IF NOT EXISTS pachaviva CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE DATABASE IF NOT EXISTS pachaviva_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

composer install
$PHP artisan migrate
$PHP artisan db:seed
$PHP artisan storage:link
```

`.env` ya apunta a `pachaviva` por MySQL, `APP_LOCALE=es`,
`APP_TIMEZONE=America/Lima`, `APP_URL=http://tours-word.test` (Laragon
detecta la carpeta automáticamente como vhost).

## Usuario admin de desarrollo

**No existe ningún seeder que cree usuarios.** El usuario admin local se creó
a mano, una sola vez, por `tinker`:

```bash
$PHP artisan tinker --execute="
App\Models\User::query()->updateOrCreate(
    ['email' => 'dev@pachaviva.test'],
    ['name' => 'Dev Local', 'password' => Hash::make('TU_PASSWORD_AQUI'), 'is_admin' => true]
);
"
```

Las credenciales de la cuenta creada esta noche están solo en el reporte de
entrega (no en git). **Esta cuenta es exclusivamente para desarrollo local y
NO debe existir en producción.** Antes de cualquier despliegue, verificar:

```sql
SELECT id, email, is_admin FROM users WHERE is_admin = 1;
```

Si aparece `dev@pachaviva.test` (o cualquier cuenta que la clienta no
reconozca), borrarla antes de publicar.

## Tests

```bash
$PHP artisan test
```

Corren contra `pachaviva_test` (MySQL, no SQLite — ver `phpunit.xml` y
[`feedback_suite_sqlite_vs_mysql`] en la memoria del equipo: las columnas de
fecha con cast se comportan distinto en SQLite y dan falsos positivos/negativos).
