<?php
include('../config.php');
header("Content-Type: text/plain");
$handle = isset($_REQUEST['handle']) ? $_REQUEST['handle'] : "";
$password = isset($_REQUEST['password']) ? $_REQUEST['password'] : "";
$result = array();
$sql = "
    SELECT COUNT(*) as nb
    FROM users
    WHERE pseudo = :handle
    AND password = :password
    ";

$req = $db->prepare($sql);
$req->bindValue(':handle', $handle);
$req->bindValue(':password', $password);
$req->execute();
$res = $req->fetchAll();
print(($res[0]['nb']));
