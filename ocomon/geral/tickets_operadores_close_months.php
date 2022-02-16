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
    $filter_areas = " AND o.sistema IN ({$u_areas}) ";

    $array_areas_names = getUserAreasNames($conn, $u_areas);

    foreach ($array_areas_names as $area_name) {
        if (strlen($areas_names))
            $areas_names .= ", ";
        $areas_names .= $area_name;
    }
}

$dates = [];
$datesBegin = [];
$datesEnd = [];
$months = [];
$operadores = [];
$data = [];

// Meses anteriores
$dates = getMonthRangesUpToNOw('P3M');
$datesBegin = $dates['ini'];
$datesEnd = $dates['end'];
$months = $dates['mLabel'];

/* PRIMEIRO BUSCO OS OPERADORES ENVOLVIDAS NA CONSULTA */
$sql = "SELECT user_id, login FROM usuarios WHERE nivel in (1, 2) ";
$result = $conn->query($sql);
foreach ($result->fetchAll() as $row) {
    $i = 0;
    foreach ($datesBegin as $dateStart) {
        /* Em cada intervalo de tempo busco os totais de cada área */

        $sqlEach = "SELECT 
                        count(*) AS total, u.login 
                    FROM 
                        ocorrencias o, usuarios u 
                    WHERE 
                        u.user_id = o.operador AND 
                        u.user_id = " . $row['user_id'] . " AND 
                        o.data_fechamento >= '" .  $dateStart  . "' AND 
                        o.data_fechamento <= '" .  $datesEnd[$i]  . "' {$filter_areas} 
                    GROUP BY login
                    ";

        
        $resultEach = $conn->query($sqlEach);

        foreach ($resultEach->fetchAll() as $rowEach) {
            
            if ($rowEach['login']){
                $operadores[] = $rowEach['login'];
                // $totais[] = (int)$rowEach['total'];
                $meses[] = $months[$i];
                $operadorDados[$rowEach['login']][] = intval($rowEach['total']);
            } else {
                $operadores[] = $row['login'];
                $operadorDados[$row['login']][] = 0;
                $meses[] = $months[$i];
            }
        }
        $i++;
    }
}



/* Ajusto os arrays de labels para não ter repetidos */
$meses = array_unique($meses);
$operadores = array_unique($operadores);

/* Separo o conteúdo para organizar o JSON */
$data['operadores'] = $operadores;
$data['months'] = $meses;
$data['totais'] = $operadorDados;
$data['chart_title'] = TRANS('TICKETS_BY_TECHNITIAN_LAST_MONTHS', '', 1);
// var_dump($operadores, $totais, $meses, $operadorDados, $data);

echo json_encode($data);

?>