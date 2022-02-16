<?php
session_start();
require_once (__DIR__ . "/" . "../../includes/include_basics_only.php");
require_once (__DIR__ . "/" . "../../includes/classes/ConnectPDO.php");
use includes\classes\ConnectPDO;

if ($_SESSION['s_logado'] != 1 || ($_SESSION['s_nivel'] != 1 && $_SESSION['s_nivel'] != 2)) {
    exit;
}

$conn = ConnectPDO::getInstance();


/* Controle para limitar os resultados das consultas às áreas do usuário logado quando a opção estiver habilitada */
$filter_areas = "";
$areas_names = "";
if (isAreasIsolated($conn) && $_SESSION['s_nivel'] != 1) {
    /* Visibilidade isolada entre áreas para usuários não admin */
    $u_areas = $_SESSION['s_uareas'];
    $filter_areas = " AND ocorrencias.sistema IN ({$u_areas}) ";

    $array_areas_names = getUserAreasNames($conn, $u_areas);

    foreach ($array_areas_names as $area_name) {
        if (strlen($areas_names))
            $areas_names .= ", ";
        $areas_names .= $area_name;
    }
}

$sql = "SELECT status.status as status, count(ocorrencias.status) AS quantidade 
        FROM status, ocorrencias 
        WHERE status.stat_id = ocorrencias.status AND status.stat_painel NOT IN (3) {$filter_areas}
        GROUP BY status ORDER BY quantidade desc";

$sql = $conn->query($sql);

$data = array();

foreach ($sql->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $data[] = $row;
}
$data[]['chart_title'] = TRANS('TICKETS_BY_STATUS', '', 1);
// IMPORTANT, output to json
echo json_encode($data);

?>
