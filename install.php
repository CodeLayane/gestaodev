<?php
/**
 * Gestão Dev ASSEGO - Instalação v4.0
 * Acesse: /install.php
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=utf-8');

$DB_HOST = 'localhost';
$DB_NAME = 'gestaodev';
$DB_USER = 'layane';
$DB_PASS = '92106115@Lore';

echo "<h2>Gestão Dev ASSEGO - Instalação v4.0</h2><pre>";

try {
    $pdo = new PDO("mysql:host=$DB_HOST;charset=utf8mb4", $DB_USER, $DB_PASS, array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ));
} catch (Exception $e) {
    die("Erro de conexão: " . $e->getMessage() . "\nVerifique DB_HOST/DB_USER/DB_PASS neste arquivo.");
}

$pdo->exec("CREATE DATABASE IF NOT EXISTS `$DB_NAME` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$pdo->exec("USE `$DB_NAME`");
$pdo->exec("SET time_zone = '-03:00'");

$tables = array(
    "usuarios" => "CREATE TABLE IF NOT EXISTS usuarios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(150) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role VARCHAR(100) DEFAULT 'dev',
        avatar_color VARCHAR(7) DEFAULT '#3b82f6',
        avatar_file VARCHAR(255) DEFAULT NULL,
        active TINYINT(1) DEFAULT 1,
        last_login DATETIME DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB",

    "sistemas" => "CREATE TABLE IF NOT EXISTS sistemas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        status VARCHAR(30) DEFAULT 'Em uso',
        department VARCHAR(100),
        technology VARCHAR(100) DEFAULT 'PHP',
        url VARCHAR(255) DEFAULT NULL,
        github_url VARCHAR(255) DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB",

    "devs_sistemas" => "CREATE TABLE IF NOT EXISTS devs_sistemas (
        system_id INT NOT NULL,
        user_id INT NOT NULL,
        PRIMARY KEY (system_id, user_id),
        FOREIGN KEY (system_id) REFERENCES sistemas(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE
    ) ENGINE=InnoDB",

    "sprints" => "CREATE TABLE IF NOT EXISTS sprints (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        goal TEXT,
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        status ENUM('Planejada','Ativa','Concluída','Cancelada') DEFAULT 'Planejada',
        created_by INT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES usuarios(id) ON DELETE SET NULL
    ) ENGINE=InnoDB",

    "demandas" => "CREATE TABLE IF NOT EXISTS demandas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200) NOT NULL,
        description TEXT,
        system_id INT,
        priority ENUM('Baixa','Média','Alta','Urgente') DEFAULT 'Média',
        status VARCHAR(30) DEFAULT 'Aberta',
        requester VARCHAR(100),
        start_date DATE,
        deadline DATE,
        completed_at DATETIME,
        sprint_id INT DEFAULT NULL,
        needs_presidency_approval TINYINT(1) DEFAULT 0,
        presidency_status ENUM('Pendente','Aprovada','Rejeitada') DEFAULT 'Pendente',
        presidency_notes TEXT,
        presidency_approved_by INT DEFAULT NULL,
        presidency_approved_at DATETIME DEFAULT NULL,
        review_observation TEXT DEFAULT NULL,
        from_solicitation_id INT DEFAULT NULL,
        created_by INT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (system_id) REFERENCES sistemas(id) ON DELETE SET NULL,
        FOREIGN KEY (sprint_id) REFERENCES sprints(id) ON DELETE SET NULL,
        FOREIGN KEY (created_by) REFERENCES usuarios(id) ON DELETE SET NULL,
        FOREIGN KEY (presidency_approved_by) REFERENCES usuarios(id) ON DELETE SET NULL
    ) ENGINE=InnoDB",

    "devs_demandas" => "CREATE TABLE IF NOT EXISTS devs_demandas (
        demand_id INT NOT NULL,
        user_id INT NOT NULL,
        assigned_by INT DEFAULT NULL,
        acceptance ENUM('Pendente','Aceita','Recusada') DEFAULT 'Pendente',
        rejection_reason TEXT,
        assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (demand_id, user_id),
        FOREIGN KEY (demand_id) REFERENCES demandas(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE
    ) ENGINE=InnoDB",

    "imagens_demandas" => "CREATE TABLE IF NOT EXISTS imagens_demandas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        demand_id INT NOT NULL,
        filename VARCHAR(255) NOT NULL,
        original_name VARCHAR(255),
        uploaded_by INT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (demand_id) REFERENCES demandas(id) ON DELETE CASCADE,
        FOREIGN KEY (uploaded_by) REFERENCES usuarios(id) ON DELETE SET NULL
    ) ENGINE=InnoDB",

    "comentarios_demandas" => "CREATE TABLE IF NOT EXISTS comentarios_demandas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        demand_id INT NOT NULL,
        user_id INT NOT NULL,
        text TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (demand_id) REFERENCES demandas(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE
    ) ENGINE=InnoDB",

    "historico_demandas" => "CREATE TABLE IF NOT EXISTS historico_demandas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        demand_id INT NOT NULL,
        user_id INT,
        action VARCHAR(255),
        old_value TEXT,
        new_value TEXT,
        details TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (demand_id) REFERENCES demandas(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE SET NULL
    ) ENGINE=InnoDB",

    "notificacoes" => "CREATE TABLE IF NOT EXISTS notificacoes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        type VARCHAR(30) DEFAULT 'info',
        title VARCHAR(200),
        message TEXT,
        link VARCHAR(255),
        entity_type VARCHAR(30),
        entity_id INT,
        is_read TINYINT(1) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE
    ) ENGINE=InnoDB",

    "registro_atividades" => "CREATE TABLE IF NOT EXISTS registro_atividades (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        action VARCHAR(255),
        entity_type VARCHAR(30),
        entity_id INT,
        details TEXT,
        ip_address VARCHAR(45) DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE SET NULL
    ) ENGINE=InnoDB",

    "avisos" => "CREATE TABLE IF NOT EXISTS avisos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200) NOT NULL,
        content TEXT,
        priority VARCHAR(20) DEFAULT 'normal',
        target_role VARCHAR(30) DEFAULT 'todos',
        pinned TINYINT(1) DEFAULT 0,
        active TINYINT(1) DEFAULT 1,
        expires_at DATE DEFAULT NULL,
        show_board TINYINT(1) DEFAULT 1,
        show_calendar TINYINT(1) DEFAULT 0,
        event_date DATE DEFAULT NULL,
        image_file VARCHAR(255) DEFAULT NULL,
        created_by INT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES usuarios(id) ON DELETE SET NULL
    ) ENGINE=InnoDB",

    "reunioes" => "CREATE TABLE IF NOT EXISTS reunioes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200) NOT NULL,
        description TEXT,
        meeting_date DATE NOT NULL,
        meeting_time TIME NOT NULL,
        duration_minutes INT DEFAULT 60,
        location VARCHAR(100),
        is_online TINYINT(1) DEFAULT 0,
        online_link VARCHAR(255),
        notes TEXT,
        agenda TEXT,
        status VARCHAR(30) DEFAULT 'Agendada',
        created_by INT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES usuarios(id) ON DELETE SET NULL
    ) ENGINE=InnoDB",

    "participantes_reunioes" => "CREATE TABLE IF NOT EXISTS participantes_reunioes (
        meeting_id INT NOT NULL,
        user_id INT NOT NULL,
        PRIMARY KEY (meeting_id, user_id),
        FOREIGN KEY (meeting_id) REFERENCES reunioes(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE
    ) ENGINE=InnoDB",

    "anotacoes_calendario" => "CREATE TABLE IF NOT EXISTS anotacoes_calendario (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        note_date DATE NOT NULL,
        content TEXT NOT NULL,
        folder VARCHAR(100) DEFAULT NULL,
        color VARCHAR(20) DEFAULT NULL,
        archived TINYINT(1) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE
    ) ENGINE=InnoDB",

    "solicitacoes" => "CREATE TABLE IF NOT EXISTS solicitacoes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200) NOT NULL,
        description TEXT,
        type VARCHAR(50) DEFAULT 'Melhoria',
        system_id INT DEFAULT NULL,
        priority ENUM('Baixa','Média','Alta','Urgente') DEFAULT 'Média',
        status ENUM('Pendente','Aprovada','Rejeitada','Convertida') DEFAULT 'Pendente',
        review_notes TEXT,
        reviewed_by INT DEFAULT NULL,
        reviewed_at DATETIME DEFAULT NULL,
        converted_demand_id INT DEFAULT NULL,
        created_by INT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (system_id) REFERENCES sistemas(id) ON DELETE SET NULL,
        FOREIGN KEY (reviewed_by) REFERENCES usuarios(id) ON DELETE SET NULL,
        FOREIGN KEY (converted_demand_id) REFERENCES demandas(id) ON DELETE SET NULL,
        FOREIGN KEY (created_by) REFERENCES usuarios(id) ON DELETE SET NULL
    ) ENGINE=InnoDB",

    "relatorios_diarios" => "CREATE TABLE IF NOT EXISTS relatorios_diarios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        report_date DATE NOT NULL,
        tasks_done TEXT,
        tasks_planned TEXT,
        blockers TEXT,
        hours_worked DECIMAL(4,1) DEFAULT 8.0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_user_date (user_id, report_date),
        FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE
    ) ENGINE=InnoDB"
);

$created = 0;
foreach ($tables as $name => $sql) {
    try {
        $pdo->exec($sql);
        echo "&#10003; $name\n";
        $created++;
    } catch (Exception $e) {
        echo "&#10007; $name: " . $e->getMessage() . "\n";
    }
}

// ===== MIGRATIONS (compatible with MySQL AND MariaDB) =====
// Check if column exists before adding
function columnExists($pdo, $table, $column) {
    $s = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
    $s->execute(array($table, $column));
    return $s->fetchColumn() > 0;
}

$migrations = array(
    array('demandas', 'sprint_id', 'INT DEFAULT NULL'),
    array('demandas', 'needs_presidency_approval', 'TINYINT(1) DEFAULT 0'),
    array('demandas', 'presidency_status', "ENUM('Pendente','Aprovada','Rejeitada') DEFAULT 'Pendente'"),
    array('demandas', 'presidency_notes', 'TEXT'),
    array('demandas', 'presidency_approved_by', 'INT DEFAULT NULL'),
    array('demandas', 'presidency_approved_at', 'DATETIME DEFAULT NULL'),
    array('demandas', 'review_observation', 'TEXT DEFAULT NULL'),
    array('sistemas', 'url', 'VARCHAR(255) DEFAULT NULL'),
    array('sistemas', 'github_url', 'VARCHAR(255) DEFAULT NULL'),
    array('registro_atividades', 'ip_address', 'VARCHAR(45) DEFAULT NULL'),
    array('usuarios', 'avatar_file', 'VARCHAR(255) DEFAULT NULL'),
    array('usuarios', 'last_login', 'DATETIME DEFAULT NULL'),
    array('avisos', 'show_board', 'TINYINT(1) DEFAULT 1'),
    array('avisos', 'active', 'TINYINT(1) DEFAULT 1'),
    array('avisos', 'show_calendar', 'TINYINT(1) DEFAULT 0'),
    array('avisos', 'event_date', 'DATE DEFAULT NULL'),
    array('avisos', 'image_file', 'VARCHAR(255) DEFAULT NULL'),
    array('anotacoes_calendario', 'folder', 'VARCHAR(100) DEFAULT NULL'),
    array('anotacoes_calendario', 'archived', 'TINYINT(1) DEFAULT 0'),
    array('anotacoes_calendario', 'color', 'VARCHAR(20) DEFAULT NULL'),
    array('demandas', 'from_solicitation_id', 'INT DEFAULT NULL'),
    array('solicitacoes', 'converted_demand_id', 'INT DEFAULT NULL')
);

$migOk = 0;
echo "\nMigrações:\n";
foreach ($migrations as $m) {
    $table = $m[0]; $column = $m[1]; $definition = $m[2];
    try {
        if (!columnExists($pdo, $table, $column)) {
            $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
            echo "  + $table.$column\n";
        }
        $migOk++;
    } catch (Exception $e) {
        echo "  ! $table.$column: " . $e->getMessage() . "\n";
    }
}
echo "Migrações: $migOk/" . count($migrations) . " OK\n";

// Multi-role migration: ENUM → VARCHAR
echo "\nMulti-role migration:\n";
try {
    $colInfo = $pdo->query("SHOW COLUMNS FROM usuarios WHERE Field='role'")->fetch();
    if ($colInfo && strpos($colInfo['Type'], 'enum') !== false) {
        $pdo->exec("ALTER TABLE usuarios MODIFY COLUMN role VARCHAR(100) DEFAULT 'dev'");
        echo "  + users.role: ENUM → VARCHAR(100) ✓\n";
    } else {
        echo "  users.role: já é VARCHAR ✓\n";
    }
} catch (Exception $e) {
    echo "  ! users.role: " . $e->getMessage() . "\n";
}

// Default admin
$check = $pdo->query("SELECT COUNT(*) as c FROM usuarios")->fetch();

// Note folders table
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS pastas_notas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        name VARCHAR(100) NOT NULL,
        color VARCHAR(20) DEFAULT '#6366f1',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");
} catch (Exception $e) {}

// Remove unique constraint on anotacoes_calendario to allow multiple notes per date
try {
    $pdo->exec("ALTER TABLE anotacoes_calendario DROP INDEX unique_user_date");
    echo "  + anotacoes_calendario: unique_user_date removed\n";
} catch (Exception $e) {}

// Tabela de arquivos (BLOB storage - sem filesystem)
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS arquivos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nome_arquivo VARCHAR(255) NOT NULL UNIQUE,
        nome_original VARCHAR(255),
        tipo_mime VARCHAR(100) DEFAULT 'application/octet-stream',
        dados LONGBLOB NOT NULL,
        criado_por INT,
        criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_nome (nome_arquivo),
        FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "  + arquivos: tabela criada\n";
} catch (Exception $e) { echo "  ~ arquivos: " . $e->getMessage() . "\n"; }

if ($check['c'] == 0) {
    $pw = password_hash('admin123', PASSWORD_BCRYPT, array('cost' => 12));
    
    // ===== USUÁRIOS =====
    $usuarios = array(
        array('Administrador', 'admin@assego.com.br', 'admin', '#3b82f6'),
        array('Gabriel', 'gabriel@assego.com.br', 'dev', '#ef4444'),
        array('Victor', 'victor@assego.com.br', 'dev', '#8b5cf6'),
        array('Matheus', 'matheus@assego.com.br', 'dev', '#f59e0b'),
        array('Luis', 'luis@assego.com.br', 'dev', '#10b981'),
        array('Ruyter', 'ruyter@assego.com.br', 'dev', '#06b6d4'),
        array('Layane', 'layane@assego.com.br', 'admin,dev', '#ec4899')
    );
    foreach ($usuarios as $u) {
        $pdo->prepare("INSERT INTO usuarios (name, email, password, role, avatar_color) VALUES (?,?,?,?,?)")
            ->execute(array($u[0], $u[1], $pw, $u[2], $u[3]));
    }
    echo "\n&#10003; " . count($usuarios) . " usuários criados (senha: admin123)\n";

    // ===== SISTEMAS =====
    $sistemas = array(
        array('Gestão Assego', 'Sistema geral da ASSEGO - com módulos de Comercial, Financeiro, Jurídico, Presidência, acesso aos colaboradores e associados', 'Em uso', 'PHP', 'sistema.assego.com.br', null),
        array('Ouvidoria Assego', 'Ouvidoria oficial da assego - Gestão das respostas', 'Em uso', 'PHP', 'https://ouvidoria.assego.com.br/', null),
        array('App Assego', 'Aplicativo oficial da assego (calculadora AC4, Calculadora reserva-militares)', 'Em uso', 'PHP', 'https://app.assego.com.br/', null),
        array('Sistema Clube', 'É um sistema de gestão para o clube, focado no controle de associados, reservas de espaços (como quiosques e campos) e administração financeira.', 'Em uso', 'PHP', 'https://clube.assego.com.br/clube/', null),
        array('Aruanã', 'Sistema de Gestão da Pousada Aruanã', 'Em uso', 'PHP', 'https://aruana.assego.com.br/gestaoAruana/index.php', null),
        array('Superação', 'O Site apresenta o Projeto Superação da ASSEGO, explicando a missão, objetivos, benefícios e como fazer a matrícula para crianças e adolescentes participarem das atividades esportivas, o formulário de cadastro, e o painel de professores para administrar as matrículas', 'Em uso', 'PHP', 'https://superacao.assego.com.br/formulario/index.html', null),
        array('Bombeiro Mirim', 'O site apresenta o projeto Bombeiro Mirim Goiás, e é possível realizar o cadastro online, através do formulário disponibilizado. Além disso possui o painel de administração para controle de cadastros e turmas', 'Em uso', 'PHP', 'https://bombeiromirimgo.com.br/formulario/index.html', null),
        array('Catracas', '', 'Em uso', 'PHP', null, null),
        array('Eventos', 'Sistema para gestão da agenda do salão e dos campos society', 'Testes', 'PHP', 'https://sistema.assego.com.br/eventos/', null),
        array('Academia', 'Sistema Low code para agenda e gestão da academia', 'Em uso', 'Low code', null, null),
        array('Photoprisma', 'Gestão de fotos da assego', 'Em uso', 'Photoprisma', null, null),
        array('Api Atacadão', 'Realizado no comercial, enviado e buscando dados do Atacadão Dia a Dia - 10% de desconto', 'Em uso', '-', null, null),
        array('Patrimônio e Estoque', 'Sistema de controle patrimonial da ASSEGO', 'Em uso', 'PHP', 'patrimonio.assego.com.br', null),
        array('Ronda', 'Sistema que usa QR codes e localização para registrar, em tempo real, rondas de segurança, limpeza e cuidado com plantas, garantindo comprovação confiável de que cada tarefa', 'Testes', 'PHP', null, null),
        array('Contratos', 'Sistema de gestão de contratos da ASSEGO', 'Em uso', 'PHP', 'https://contrato.assego.com.br/login.php', null),
        array('Comissões', 'Sistema de dados e controle das comissões da ASSEGO', 'Em uso', 'PHP', 'https://comissao.assego.com.br/login.php', null),
        array('Lideranças', 'Sistema de dados e controle das lideranças associadas a ASSEGO', 'Pausado', 'PHP', 'http://172.16.253.44/layane/sistema_liderancas/login.php', null),
        array('Site', 'Site oficial da Associação', 'Em uso', 'React', 'assego.com.br', null),
        array('Censo 2025', 'Avaliação Psicossocial + painel de respostas', 'Não utilizado', 'PHP', 'https://ap.assego.com.br/', null),
        array('Convites Posse Assego', 'Geração de QR codes de confirmação para evento', 'Não utilizado', 'PHP', 'https://posse.assego.com.br/QR/evento-1/login.php', null),
        array('Help Desk Assego', 'Controle e abertura de chamados internos na assego', 'Em uso', 'PHP', 'https://chamados.assego.com.br/', null),
        array('Relatórios Diários', 'Sistema de relatórios de equipe diários', 'Não utilizado', 'PHP', null, null),
        array('Demandas Dev', 'Sistema de controle de demandas dos desenvolvedores assego', 'Em uso', 'PHP', null, null),
        array('Página Integrada de Sistemas', 'Página de controle dos sistemas da assego', 'Em uso', 'PHP', null, null)
    );
    foreach ($sistemas as $s) {
        $pdo->prepare("INSERT INTO sistemas (name, description, status, technology, url, github_url) VALUES (?,?,?,?,?,?)")
            ->execute($s);
    }
    echo "&#10003; " . count($sistemas) . " sistemas criados\n";

    // ===== DEVS ↔ SISTEMAS =====
    // user_ids: 2=Gabriel, 3=Victor, 4=Matheus, 5=Luis, 6=Ruyter, 7=Layane
    $devSistemas = array(
        // 1-Gestão Assego: Gabriel, Victor, Matheus, Luis
        array(1,2), array(1,3), array(1,4), array(1,5),
        // 2-Ouvidoria: Gabriel
        array(2,2),
        // 3-App Assego: Matheus
        array(3,4),
        // 4-Sistema Clube: Gabriel, Victor, Luis
        array(4,2), array(4,3), array(4,5),
        // 5-Aruanã: Gabriel, Victor, Luis
        array(5,2), array(5,3), array(5,5),
        // 6-Superação: Gabriel, Victor, Luis
        array(6,2), array(6,3), array(6,5),
        // 7-Bombeiro Mirim: Gabriel, Victor, Luis
        array(7,2), array(7,3), array(7,5),
        // 8-Catracas: Victor
        array(8,3),
        // 9-Eventos: Gabriel, Victor, Luis
        array(9,2), array(9,3), array(9,5),
        // 10-Academia: Victor
        array(10,3),
        // 11-Photoprisma: Luis
        array(11,5),
        // 12-Api Atacadão: Victor
        array(12,3),
        // 13-Patrimônio: Ruyter
        array(13,6),
        // 14-Ronda: Ruyter
        array(14,6),
        // 15-Contratos: Matheus
        array(15,4),
        // 16-Comissões: Layane
        array(16,7),
        // 17-Lideranças: Layane
        array(17,7),
        // 18-Site: Layane
        array(18,7),
        // 19-Censo 2025: Layane
        array(19,7),
        // 20-Convites Posse: Layane
        array(20,7),
        // 21-Help Desk: Matheus
        array(21,4),
        // 22-Relatórios Diários: Matheus
        array(22,4),
        // 23-Demandas Dev: Layane
        array(23,7),
        // 24-Página Integrada: Layane
        array(24,7)
    );
    foreach ($devSistemas as $ds) {
        $pdo->prepare("INSERT INTO devs_sistemas (system_id, user_id) VALUES (?,?)")->execute($ds);
    }
    echo "&#10003; " . count($devSistemas) . " atribuições dev↔sistema criadas\n";
    
    echo "\n&#10003; Instalação com dados completa!\n";
    echo "Todos os usuários usam senha: admin123\n";
}

echo "\nInstalação concluída! ($created tabelas)\n";
echo "\n<a href='check.php'>→ Verificar diagnóstico</a>";
echo "\n<a href='index.php'>→ Acessar o sistema</a>\n";
echo "</pre>";