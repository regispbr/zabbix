<?php
require_once dirname(__FILE__) . '/../../../include/config.inc.php';
require_once dirname(__FILE__) . '/../../../include/functions.inc.php';

// Set JSON response header
header('Content-Type: application/json');

// Check if user is authenticated
if (!CWebUser::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

// Check if file was uploaded
if (!isset($_FILES['icon']) || $_FILES['icon']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'Erro no upload do arquivo']);
    exit;
}

$file = $_FILES['icon'];

// Validate file size (2MB max)
$maxSize = 2 * 1024 * 1024;
if ($file['size'] > $maxSize) {
    echo json_encode(['success' => false, 'error' => 'Arquivo muito grande. Máximo: 2MB']);
    exit;
}

// Validate file type
$allowedTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/gif', 'image/svg+xml'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedTypes)) {
    echo json_encode(['success' => false, 'error' => 'Formato não suportado. Use PNG, JPG, GIF ou SVG']);
    exit;
}

// Generate unique filename
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = uniqid('icon_') . '.' . $extension;

// Create assets directory if it doesn't exist
$assetsDir = __DIR__ . '/assets';
if (!is_dir($assetsDir)) {
    if (!mkdir($assetsDir, 0755, true)) {
        echo json_encode(['success' => false, 'error' => 'Erro ao criar diretório de assets']);
        exit;
    }
}

// Move uploaded file
$targetPath = $assetsDir . '/' . $filename;
if (move_uploaded_file($file['tmp_name'], $targetPath)) {
    // Clean up old icons (keep only last 10 icons)
    $icons = glob($assetsDir . '/icon_*');
    if (count($icons) > 10) {
        usort($icons, function($a, $b) {
            return filemtime($a) - filemtime($b);
        });
        
        $toRemove = array_slice($icons, 0, count($icons) - 10);
        foreach ($toRemove as $oldIcon) {
            unlink($oldIcon);
        }
    }
    
    echo json_encode(['success' => true, 'filename' => $filename]);
} else {
    echo json_encode(['success' => false, 'error' => 'Erro ao salvar arquivo']);
}
?>
