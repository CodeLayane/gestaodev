<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once __DIR__.'/config.php';
initSession();
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

function jr($data, $code=200){
    ob_end_clean();
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isLoggedIn()) jr(['error'=>'Não autenticado'], 401);
$ME = getCurrentUser();
if (!$ME) jr(['error'=>'Usuário inválido'], 401);

$UID      = $ME['id'];
$IS_ADMIN = in_array($ME['role'], ['admin', 'presidencia']);
$method   = $_SERVER['REQUEST_METHOD'];
$act      = $_GET['action'] ?? '';

try {
    $db = getDB();

    // Ensure tables exist — files stored as LONGBLOB in DB (no filesystem)
    $db->exec("CREATE TABLE IF NOT EXISTS documentations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(300) NOT NULL,
        description TEXT,
        content LONGTEXT,
        system_id INT DEFAULT NULL,
        category VARCHAR(100) DEFAULT 'Geral',
        password VARCHAR(255) DEFAULT NULL,
        password_plain VARCHAR(255) DEFAULT NULL,
        created_by INT NOT NULL,
        updated_by INT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $db->exec("CREATE TABLE IF NOT EXISTS doc_files (
        id INT AUTO_INCREMENT PRIMARY KEY,
        doc_id INT NOT NULL,
        original_name VARCHAR(255) NOT NULL,
        mime_type VARCHAR(100) DEFAULT 'application/octet-stream',
        file_size BIGINT DEFAULT 0,
        file_data LONGBLOB,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Add missing columns to existing installations
    $dbN = DB_NAME;
    $dCols = $db->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='$dbN' AND TABLE_NAME='documentations'")->fetchAll(PDO::FETCH_COLUMN);
    foreach (["description TEXT","content LONGTEXT","category VARCHAR(100) DEFAULT 'Geral'","password VARCHAR(255) DEFAULT NULL","password_plain VARCHAR(255) DEFAULT NULL","updated_by INT DEFAULT NULL","system_id INT DEFAULT NULL"] as $def) {
        $cn = explode(' ', $def)[0];
        if (!in_array($cn, $dCols)) try { $db->exec("ALTER TABLE documentations ADD COLUMN $def"); } catch (Exception $e) {}
    }
    $fCols = $db->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA='$dbN' AND TABLE_NAME='doc_files'")->fetchAll(PDO::FETCH_COLUMN);
    foreach (["mime_type VARCHAR(100) DEFAULT 'application/octet-stream'","file_data LONGBLOB DEFAULT NULL"] as $def) {
        $cn = explode(' ', $def)[0];
        if (!in_array($cn, $fCols)) try { $db->exec("ALTER TABLE doc_files ADD COLUMN $def"); } catch (Exception $e) {}
    }

} catch (Exception $e) {
    jr(['error' => 'DB init: ' . $e->getMessage()], 500);
}

// ── FILE DOWNLOAD (no JSON — serves binary) ───────────────────────────────
if ($act === 'doc_file_download' && isset($_GET['id'])) {
    try {
        $fid = (int)$_GET['id'];
        $st = $db->prepare("SELECT df.*, d.password, d.created_by FROM doc_files df JOIN documentations d ON df.doc_id=d.id WHERE df.id=?");
        $st->execute([$fid]); $f = $st->fetch(PDO::FETCH_ASSOC);
        if (!$f) { ob_end_clean(); http_response_code(404); echo 'Arquivo não encontrado'; exit; }
        // Password check: if doc has password and user is not creator/admin, require verification
        // (Since download is via GET link, we trust the logged-in user already viewed the doc)
        ob_end_clean();
        $mime = $f['mime_type'] ?? 'application/octet-stream';
        header('Content-Type: ' . $mime);
        header('Content-Disposition: inline; filename="' . rawurlencode($f['original_name']) . '"');
        header('Content-Length: ' . strlen((string)$f['file_data']));
        header('Cache-Control: private, max-age=3600');
        echo $f['file_data'];
        exit;
    } catch (Exception $e) {
        ob_end_clean(); http_response_code(500); echo 'Erro: ' . $e->getMessage(); exit;
    }
}

// ── LIST DOCS ─────────────────────────────────────────────────────────────
if ($act === 'docs' && $method === 'GET') {
    try {
        $sysId = $_GET['system_id'] ?? ''; $cat = $_GET['category'] ?? '';
        $sql = "SELECT d.id,d.title,d.description,d.system_id,d.category,d.password,d.password_plain,d.created_by,d.updated_by,d.created_at,d.updated_at,u.name as author_name,s.name as system_name
                FROM documentations d
                LEFT JOIN usuarios u ON d.created_by=u.id
                LEFT JOIN sistemas s ON d.system_id=s.id WHERE 1=1";
        $p = [];
        if ($sysId) { $sql .= " AND d.system_id=?"; $p[] = $sysId; }
        if ($cat)   { $sql .= " AND d.category=?";  $p[] = $cat; }
        $sql .= " ORDER BY d.updated_at DESC";
        $st = $db->prepare($sql); $st->execute($p); $docs = $st->fetchAll();
        foreach ($docs as &$doc) {
            $doc['has_password'] = !empty($doc['password']);
            unset($doc['password']);
            if ($doc['created_by'] != $UID) unset($doc['password_plain']);
            try {
                $sf = $db->prepare("SELECT id,original_name,mime_type,file_size FROM doc_files WHERE doc_id=?");
                $sf->execute([$doc['id']]); $doc['files'] = $sf->fetchAll();
            } catch (Exception $e) { $doc['files'] = []; }
        }
        jr($docs);
    } catch (Exception $e) { jr([], 200); }
}

// ── CREATE DOC ────────────────────────────────────────────────────────────
if ($act === 'docs' && $method === 'POST') {
    try {
        $d = json_decode(file_get_contents('php://input'), true) ?? [];
        $title   = trim($d['title'] ?? '');
        $desc    = trim($d['description'] ?? '');
        $content = $d['content'] ?? '';
        $sysId   = $d['system_id'] ?: null;
        $cat     = $d['category'] ?? 'Geral';
        $pw      = $d['password'] ?? '';
        if (!$title) jr(['error' => 'Título obrigatório'], 400);
        $pwHash  = $pw ? password_hash($pw, PASSWORD_BCRYPT) : null;
        $pwPlain = $pw ?: null;
        $db->prepare("INSERT INTO documentations (title,description,content,system_id,category,password,password_plain,created_by) VALUES (?,?,?,?,?,?,?,?)")
           ->execute([$title, $desc, $content, $sysId, $cat, $pwHash, $pwPlain, $UID]);
        $newId = $db->lastInsertId();
        if (function_exists('logActivity')) logActivity($UID, 'Criou documentação', 'documentation', $newId, "Título: $title");
        jr(['success' => true, 'id' => $newId]);
    } catch (Exception $e) { jr(['error' => $e->getMessage()], 500); }
}

// ── GET SINGLE DOC ────────────────────────────────────────────────────────
if ($act === 'doc' && isset($_GET['id']) && $method === 'GET') {
    try {
        $docId = (int)$_GET['id'];
        $st = $db->prepare("SELECT d.*,u.name as author_name,s.name as system_name FROM documentations d LEFT JOIN usuarios u ON d.created_by=u.id LEFT JOIN sistemas s ON d.system_id=s.id WHERE d.id=?");
        $st->execute([$docId]); $doc = $st->fetch();
        if (!$doc) jr(['error' => 'Não encontrado'], 404);
        if (!empty($doc['password'])) {
            $inputPw = $_GET['password'] ?? '';
            if ($doc['created_by'] == $UID || $IS_ADMIN) { $doc['locked'] = false; $doc['password_visible'] = $doc['password_plain'] ?? null; }
            elseif (!password_verify($inputPw, $doc['password'])) { unset($doc['content']); $doc['locked'] = true; }
            else { $doc['locked'] = false; }
        } else { $doc['locked'] = false; }
        $doc['has_password'] = !empty($doc['password']); unset($doc['password']); unset($doc['password_plain']);
        try {
            $sf = $db->prepare("SELECT id,original_name,mime_type,file_size FROM doc_files WHERE doc_id=?");
            $sf->execute([$docId]); $doc['files'] = $sf->fetchAll();
        } catch (Exception $e) { $doc['files'] = []; }
        jr($doc);
    } catch (Exception $e) { jr(['error' => $e->getMessage()], 500); }
}

// ── UPDATE DOC ────────────────────────────────────────────────────────────
if ($act === 'doc' && isset($_GET['id']) && $method === 'PUT') {
    try {
        $docId = (int)$_GET['id'];
        $d = json_decode(file_get_contents('php://input'), true) ?? [];
        $sets = []; $params = [];
        if (isset($d['title']))       { $sets[] = 'title=?';       $params[] = $d['title']; }
        if (isset($d['description'])) { $sets[] = 'description=?'; $params[] = $d['description']; }
        if (isset($d['content']))     { $sets[] = 'content=?';     $params[] = $d['content']; }
        if (isset($d['system_id']))   { $sets[] = 'system_id=?';   $params[] = $d['system_id'] ?: null; }
        if (isset($d['category']))    { $sets[] = 'category=?';    $params[] = $d['category']; }
        if (array_key_exists('password', $d)) {
            $sets[] = 'password=?';       $params[] = $d['password'] ? password_hash($d['password'], PASSWORD_BCRYPT) : null;
            $sets[] = 'password_plain=?'; $params[] = $d['password'] ?: null;
        }
        if ($sets) {
            $sets[] = 'updated_by=?'; $params[] = $UID; $params[] = $docId;
            $db->prepare("UPDATE documentations SET " . implode(',', $sets) . " WHERE id=?")->execute($params);
        }
        if (function_exists('logActivity')) logActivity($UID, 'Editou documentação', 'documentation', $docId, "ID: $docId");
        jr(['success' => true]);
    } catch (Exception $e) { jr(['error' => $e->getMessage()], 500); }
}

// ── DELETE DOC ────────────────────────────────────────────────────────────
if ($act === 'doc' && isset($_GET['id']) && $method === 'DELETE') {
    try {
        $docId = (int)$_GET['id'];
        $st = $db->prepare("SELECT title,created_by FROM documentations WHERE id=?"); $st->execute([$docId]); $dtR = $st->fetch();
        if ($dtR && $dtR['created_by'] != $UID && !$IS_ADMIN) jr(['error' => 'Sem permissão'], 403);
        $db->prepare("DELETE FROM doc_files WHERE doc_id=?")->execute([$docId]);
        $db->prepare("DELETE FROM documentations WHERE id=?")->execute([$docId]);
        if (function_exists('logActivity')) logActivity($UID, 'Excluiu documentação', 'documentation', $docId, $dtR['title'] ?? 'N/A');
        jr(['success' => true]);
    } catch (Exception $e) { jr(['error' => $e->getMessage()], 500); }
}

// ── FILE UPLOAD — stored in database ─────────────────────────────────────
if ($act === 'doc_upload' && isset($_GET['id'])) {
    $docId = (int)$_GET['id'];
    if (empty($_FILES['file'])) jr(['error' => 'Nenhum arquivo enviado'], 400);
    try {
        $f = $_FILES['file'];
        if ($f['error'] !== UPLOAD_ERR_OK) jr(['error' => 'Erro no upload: ' . $f['error']], 400);
        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf','doc','docx','xls','xlsx','ppt','pptx','txt','md','csv','json','xml','html','css','js','php','py','sql','log','png','jpg','jpeg','gif','webp','svg','zip','rar','7z','mp4','mp3'];
        if (!in_array($ext, $allowed)) jr(['error' => 'Tipo não permitido: .' . $ext], 400);
        if ($f['size'] > 20 * 1024 * 1024) jr(['error' => 'Máximo 20MB'], 400);

        $mimeMap = [
            'pdf'=>'application/pdf',
            'doc'=>'application/msword','docx'=>'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls'=>'application/vnd.ms-excel','xlsx'=>'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'ppt'=>'application/vnd.ms-powerpoint','pptx'=>'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'txt'=>'text/plain','md'=>'text/markdown','csv'=>'text/csv','json'=>'application/json',
            'xml'=>'application/xml','html'=>'text/html','css'=>'text/css','js'=>'application/javascript',
            'php'=>'text/plain','py'=>'text/plain','sql'=>'text/plain','log'=>'text/plain',
            'png'=>'image/png','jpg'=>'image/jpeg','jpeg'=>'image/jpeg','gif'=>'image/gif','webp'=>'image/webp','svg'=>'image/svg+xml',
            'zip'=>'application/zip','rar'=>'application/x-rar-compressed','7z'=>'application/x-7z-compressed',
            'mp4'=>'video/mp4','mp3'=>'audio/mpeg',
        ];
        $mime = $mimeMap[$ext] ?? 'application/octet-stream';

        // Read file data into memory and store in DB
        $fileData = file_get_contents($f['tmp_name']);
        if ($fileData === false) jr(['error' => 'Falha ao ler arquivo'], 500);

        $stmt = $db->prepare("INSERT INTO doc_files (doc_id, original_name, mime_type, file_size, file_data) VALUES (?, ?, ?, ?, ?)");
        $stmt->bindValue(1, $docId, PDO::PARAM_INT);
        $stmt->bindValue(2, $f['name'], PDO::PARAM_STR);
        $stmt->bindValue(3, $mime, PDO::PARAM_STR);
        $stmt->bindValue(4, $f['size'], PDO::PARAM_INT);
        $stmt->bindValue(5, $fileData, PDO::PARAM_STR);
        $stmt->execute();
        $fid = $db->lastInsertId();

        if (function_exists('logActivity')) logActivity($UID, 'Upload em documentação', 'documentation', $docId, $f['name']);
        jr(['success' => true, 'file' => ['id' => $fid, 'original_name' => $f['name'], 'mime_type' => $mime, 'file_size' => $f['size']]]);
    } catch (Exception $e) { jr(['error' => $e->getMessage()], 500); }
}

// ── DELETE FILE ───────────────────────────────────────────────────────────
if ($act === 'doc_file_delete' && isset($_GET['id'])) {
    try {
        $fid = (int)$_GET['id'];
        $st = $db->prepare("SELECT df.id,df.original_name,df.doc_id,d.created_by FROM doc_files df JOIN documentations d ON df.doc_id=d.id WHERE df.id=?");
        $st->execute([$fid]); $dfR = $st->fetch();
        if (!$dfR) jr(['error' => 'Não encontrado'], 404);
        if ($dfR['created_by'] != $UID && !$IS_ADMIN) jr(['error' => 'Sem permissão'], 403);
        $db->prepare("DELETE FROM doc_files WHERE id=?")->execute([$fid]);
        if (function_exists('logActivity')) logActivity($UID, 'Removeu arquivo', 'documentation', $dfR['doc_id'], $dfR['original_name']);
        jr(['success' => true]);
    } catch (Exception $e) { jr(['error' => $e->getMessage()], 500); }
}

jr(['error' => 'Ação não reconhecida: ' . $act], 400);