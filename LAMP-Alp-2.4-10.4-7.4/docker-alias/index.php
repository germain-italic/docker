<h1>Connection</h1>
<ul>
    <li>FIREWALL_WHITELIST_HOSTS: <code><?php echo $_ENV['FIREWALL_WHITELIST_HOSTS'] ?></code></li>
    <li>FIREWALL_WHITELIST_IPS: <code><?php echo $_ENV['FIREWALL_WHITELIST_IPS'] ?></code></li>
    <li>REMOTE_ADDR: <code><?php echo $_SERVER['REMOTE_ADDR'] ?></code></li>
    <li>SERVER_NAME: <code><?php echo $_SERVER['SERVER_NAME'] ?></code></li>
    <li>HOSTNAME: <code><?php echo $_SERVER['HOSTNAME'] ?></code></li>
</ul>
<?php
if (!strstr($_SERVER['SERVER_NAME'], $_ENV['FIREWALL_WHITELIST_IPS'])
 && !strstr($_SERVER['SERVER_NAME'], $_ENV['FIREWALL_WHITELIST_HOSTS'])
) {
    header('HTTP/1.0 404 Not found.');
    exit();
} else {
    ?>
    <h1>Shortcuts</h1>
    <ul>
        <li><a href="http://<?php echo $_SERVER['HTTP_HOST'] ?>/<?php echo $_ENV['DOCKER_ALIAS'] ?>">phpinfo</a></li>
        <li><a href="adminer.php?server=db&username=root">Adminer</a></li>
        <li><a href="http://localhost:<?php echo $_ENV['PORTAINER_PORT'] ?>">Portainer</a></li>
    </ul>


    <?php
    $sql = 'SHOW DATABASES';
    ?>

    <h1>Connect using <code>mysqli</code> (object)</h1>
    <?php
    if (class_exists('mysqli')) {
        $mysqli = new mysqli($_ENV['MYSQL_HOST'], $_ENV['MYSQL_USER'], $_ENV['MYSQL_PASSWORD'], $_ENV['MYSQL_DATABASE']);
        if ($mysqli->connect_errno) {
            printf("Error: %s", $mysqli->connect_error);
        } else {
            if ($result = $mysqli->query($sql)) {
                while($obj = $result->fetch_object()){
                    echo $obj->Database.'<br>';
                }
            } else {
                echo "No database<br>";
            }
            echo "&rarr; OK";
        }
    } else {
        echo "&rarr; Class 'mysqli' not found";
    }
    ?>


    <h1>Connect using <code>mysqli</code> (procedural)</h1>
    <?php
    if (function_exists('mysqli_connect')) {
        $link = mysqli_connect($_ENV['MYSQL_HOST'], $_ENV['MYSQL_USER'], $_ENV['MYSQL_PASSWORD']);
        if (mysqli_connect_errno()) {
            printf("Error: %s", mysqli_connect_error());
        } else {
            if ($result = $link->query($sql)) {
                while($obj = $result->fetch_object()){
                    echo $obj->Database.'<br>';
                }
            } else {
                echo "No database<br>";
            }
            echo "&rarr; OK";
        }
    } else {
        echo "&rarr; Function 'mysqli' not mysqli_connect";
    }
    ?>
    
    <h1>Connect using <code>pdo_mysql</code></h1>
    <?php
    try {
        $dbh = new PDO('mysql:host='.$_ENV['MYSQL_HOST'].';', $_ENV['MYSQL_USER'], $_ENV['MYSQL_PASSWORD']);
        if (!empty($dbh->query($sql))) {
            foreach($dbh->query($sql) as $row) {
                echo $row['Database'].'<br>';
            }
        } else {
            echo "Error: no database<br>";
        }
        echo "&rarr; OK";
    } catch (PDOException $e) {
        printf("Error: %s", $e->getMessage());
    }
    
    
    phpinfo();
}
?>