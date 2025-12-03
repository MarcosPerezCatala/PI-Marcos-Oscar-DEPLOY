<?php
namespace Deployer;

// Requerimos la receta común (common.php) para aplicaciones PHP estándar.
require 'recipe/common.php'; 

// === CONFIGURACIÓN GLOBAL ===
set('application', 'deployer-cipfpbatoi'); 
set('repository', 'https://github.com/MarcosPerezCatala/PI-Marcos-Oscar-DEPLOY'); // << CAMBIAR AQUÍ

// El número de versiones anteriores que se mantendrán en el servidor.
set('keep_releases', 5);

// === CONFIGURACIÓN DE HOST ===
// Define la conexión al servidor remoto (Tu instancia AWS)
host('3.235.29.169') 
    ->set('remote_user', 'sa04-deployer')
    ->set('identity_file', 'MarcosLaravel.pem') // << CAMBIAR AQUÍ: Nombre de tu clave SSH local
    ->set('deploy_path', '/var/www/es-cipfpbatoi-deployer/html')
    // Asignar tty para el clonado de Git.
    ->set('git_tty', true)
    ->set('git_recursive', true);


// === DIRECTORIOS Y ARCHIVOS COMPARTIDOS ===
// Archivos que se mantendrán entre versiones (ej. configuración y credenciales)
set('shared_files', [
    'backend/includes/json_connect.php' 
]);

// Directorios que se mantendrán entre versiones (ej. datos y archivos subidos por el usuario)
set('shared_dirs', [
    'backend/data',   
    'backend/uploads'  
]);

// Directorios a los que el servidor web necesita permiso de escritura (grupo www-data)
set('writable_dirs', [
    'backend/data',
    'backend/uploads'
]);


// === TAREAS PERSONALIZADAS ===

// Sobreescribimos la tarea estándar de Composer para que se ejecute en el subdirectorio 'backend'
task('deploy:vendors', function () {
    // Instalamos las dependencias solo en la carpeta 'backend' donde está composer.json
    run('cd {{release_path}}/backend && composer install --no-dev --no-interaction --prefer-dist');
});

// Tarea para reiniciar php-fpm (necesario para la caché)
task('reload:php-fpm', function () {
    // Comando que ya configuraste en sudoers (Asegura la versión de PHP: php8.3-fpm)
    run('sudo /etc/init.d/php8.3-fpm restart'); 
})->desc('Reinicia el servei php-fpm per netejar OpCache');


// === FLUJO DE DESPLIEGUE ===
// Usamos el flujo de 'common' pero con el paso de vendors personalizado
desc('Deploy your project');
task('deploy', [
    'deploy:prepare',           // Prepara el directorio de despliegue
    'deploy:vendors',           // Instala las dependencias (dentro de backend/)
    'deploy:update_code',       // Clona el repositorio
    'deploy:shared',            // Crea enlaces a archivos/directorios compartidos
    'deploy:writable',          // Asigna permisos de escritura
    'deploy:symlink',           // Cambia el enlace 'current' a la nueva versión
    'deploy:cleanup',           // Elimina versiones antiguas
    'deploy:success',           // Notifica éxito
]);

// Desbloquear si el despliegue falla
after('deploy:failed', 'deploy:unlock');

// Ejecutar el reinicio de php-fpm después del despliegue exitoso
after('deploy', 'reload:php-fpm');
