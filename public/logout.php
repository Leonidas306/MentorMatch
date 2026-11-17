<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/src/bootstrap.php';

cerrar_sesion_usuario();
header('Location: index.php');
exit;
