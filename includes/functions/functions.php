<?php /*                        Copyright 2020 Flávio Ribeiro

This file is part of OCOMON.

OCOMON is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

OCOMON is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Foobar; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

ini_set('display_errors', 0);


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


function isPHPOlder(){
    if (version_compare(phpversion(), '7.0', '<' )){
        return true;
    }
    return false;
}

/**
 * Retona se o requisito da versão 3.0 quanto ao SQL_MODE do MySQL está ok
 * @return bool
 */
function isSqlModeOk ($conn) {
    
    $key = "ONLY_FULL_GROUP_BY";
    $sqlMode = "SELECT @@sql_mode";

    try {
        $resSqlMode = $conn->query($sqlMode);
        $haystack = $resSqlMode->fetch()['@@sql_mode'];

        $pieces = explode(',', $haystack);

        foreach ($pieces as $piece) {
            if (trim(strtolower($piece)) == strtolower($key)) {
                return false;
            }
        }
    }
    catch (Exception $e) {
        return false;
    }

    return true;
}


if (!function_exists('ereg')) {
    function ereg($pattern, $subject, &$matches = array())
    {
        return preg_match('/' . $pattern . '/', $subject, $matches);
    }
}

if (!function_exists('eregi')) {
    function eregi($pattern, $subject, &$matches = array())
    {
        return preg_match('/' . $pattern . '/i', $subject, $matches);
    }
}



/**
 * Retorna o valor formatado no formato de moeda Brasileiro
 * @param string $price
 * @return string
 */
function priceScreen(?string $price): string
{
    return number_format((!empty($price) ? $price : 0), 2, ",", ".");
}

/**
 * Retorna o valor formatado no formato float para gravar no banco
 * @param string $price
 * @return string
 */
function priceDB(?string $price): string
{
    $price = (!empty($price) ? str_replace('.','', $price) : '');
    $price = (!empty($price) ? str_replace(',','.', $price) : '');
    
    // return number_format((!empty($price) ? $price : 0), 2, ".", ",");
    return $price;
}


/**
 * Adiciona o recurso multibyte na funçao uc_first
 */
if (!function_exists('mb_ucfirst')) {
    function mb_ucfirst(string $str, string $encoding = null): string
    {
        if ($encoding === null) {
            $encoding = mb_internal_encoding();
        }
        return mb_strtoupper(mb_substr($str, 0, 1, $encoding), $encoding) . mb_substr($str, 1, null, $encoding);
    }
}

/* caso a extensao mbstrings nao tenha sido instalada */
if (!function_exists('mb_strtolower')) {
    function mb_strtolower(string $str): string
    {
        return strtolower($str);
    }
}

/**
 * Retorna a string com apenas a primeira letra em caixa alta.
 */
function firstLetterUp(string $str): string
{
    return mb_ucfirst(mb_strtolower($str));
}

/**
 * Retorna apenas a primeira palavra da string.
 */
function firstWord(string $str): string
{
    return explode(" ", $str)[0];
}



function NVL($value)
{
    if ($value == '') {
        return '&nbsp';
    }
    return $value;
}


function valueSeparator($value, $sep)
{
    $notSep = "";
    if ($sep == ".") {
        $notSep = ",";
    }

    if ($sep == ",") {
        $notSep = ".";
    }

    if (strpos($value, $notSep)) {
        $value = str_replace($notSep, $sep, $value);
    }
    if (!strpos($value, $sep)) {
        $value .= $sep . "00";
    }

    return $value;
}

/**
 * Retorna o array com as informações de configuração do sistema
 */
function getConfig ($conn): array
{
    $sql = "SELECT * FROM config ";
    try {
        $res = $conn->query($sql);
        if ($res->rowCount())
            return $res->fetch();
        return [];
    }
    catch (Exception $e) {
        return [];
    }
}

/**
 * Retorna se a configuração atual está marcada para isolamento de visibilidade entre áreas
 */
function isAreasIsolated($conn): bool
{
    $config = getConfig($conn);
    if ($config['conf_isolate_areas'] == 1)
        return true;
    return false;
}

/**
 * Retorna um array com os nomes das áreas que o usuário logado faz parte
 */
function getUserAreasNames($conn, $areasIds): array
{
    $names = [];
    $sql = "SELECT sistema FROM sistemas WHERE sis_id IN ({$areasIds}) ORDER BY sistema";
    try {
        $res = $conn->query($sql);
        foreach ($res->fetchall() as $row) {
            $names[] = $row['sistema'];
        }
        return $names;
    }
    catch (Exception $e) {
        return [];
    }
}

/**
 * Retorna o array com as informações da tabela de equipamentos
 * Podem ser passados os dados de etiqueta (unidade e etiqueta) ou o código da tabela de equipamentos
 * Retorna o array vazio se não localizar o registro
 * @param PDO $conn variável de conexão
 * @param int $unit código da unidade
 * @param varchar $tag etiqueta do equipamento
 * @param int $cod código do equipamento na tabela de equipamentos
 */
function getEquipmentInfo ($conn, $unit, $tag, $cod = null): array
{

    $terms = "";
    if (!empty($cod)) {
        $terms .= " AND comp_cod = '{$cod}' ";
    } elseif (empty($unit) || empty($tag)) {
        return [];
    }
    
    if (empty($cod)) {
        $terms .= " AND comp_inv = '{$tag}' AND comp_inst = '{$unit}' ";
    }

    $sql = "SELECT * FROM equipamentos WHERE 1 = 1 {$terms} ";
    try {
        $res = $conn->query($sql);
        if ($res->rowCount())
            return $res->fetch();
        return [];
    }
    catch (Exception $e) {
        return [];
    }
}


/**
 * Retorna um array com a listagem de fabricantes
 * @var PDO $conn
 * @var int $type: 1: hw | 2: sw | 0: any(default)
 */
function getManufacturers ($conn, $type = 0): array
{
    $empty = [];
    
    $terms = ($type != 0 ? "WHERE fab_tipo IN ({$type},3) OR fab_tipo IS NULL " : '');

    $sql = "SELECT * FROM fabricantes {$terms} ORDER BY fab_nome";
    try {
        $res = $conn->query($sql);
        if ($res->rowCount()) {
            foreach ($res->fetchall() as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return $empty;
        // if ($res->rowCount())
        //     return $res->fetch();
        // return $empty;
    }
    catch (Exception $e) {
        return $empty;
    }
}

/**
 * Retorna um array com as informações do componente interno (não avulso)
 */
function getPeripheralInfo ($conn, $peripheralCod): array
{
    $empty = [];
    $empty['mdit_cod'] = "";
    $empty['mdit_manufacturer'] = "";
    $empty['mdit_fabricante'] = "";
    $empty['mdit_desc'] = "";
    $empty['mdit_desc_capacidade'] = "";
    $empty['mdit_sufixo'] = "";
    
    if (empty($peripheralCod)) {
        return $empty;
    }
        
    $sql = "SELECT * FROM modelos_itens WHERE mdit_cod = '{$peripheralCod}'";
    try {
        $res = $conn->query($sql);
        if ($res->rowCount())
            return $res->fetch();
        return $empty;
    }
    catch (Exception $e) {
        return $empty;
    }
}


/**
 * Retorna um array com as informações de horarios da jornada de trabalho
 */
function getWorktime ($conn, $profileId): array
{
    $empty = [];
    
    if (empty($profileId)) {
        return $empty;
    }
        
    $sql = "SELECT * FROM worktime_profiles WHERE id = '{$profileId}'";
    try {
        $res = $conn->query($sql);
        if ($res->rowCount())
            return $res->fetch();
        return $empty;
    }
    catch (Exception $e) {
        return $empty;
    }
}

/* Retorna uma tela com as informações estruturadas do perfil de jornada */
function getWorktimeSets (array $worktime) {
    
    $empty = [];
    $empty['week'] = "";
    $empty['sat'] = "";
    $empty['sun'] = "";
    $empty['off'] = "";
    
    
    if (empty($worktime)) {
        return $empty;
    }

    $wt = $worktime;

    if ($wt['week_ini_time_hour'] == "00" && $wt['week_ini_time_minute'] == "00" && $wt['week_end_time_hour'] == "00" && $wt['week_end_time_minute'] == "00") {
        $empty['week'] = TRANS('OFF_TIME');
    } else {
        $empty['week'] = TRANS('TIME_FROM') . " " . $wt['week_ini_time_hour'] . ":" . $wt['week_ini_time_minute'] . " " . TRANS('TIME_TO') . " " . $wt['week_end_time_hour'] . ":" . $wt['week_end_time_minute'];
    }

    if ($wt['sat_ini_time_hour'] == "00" && $wt['sat_ini_time_minute'] == "00" && $wt['sat_end_time_hour'] == "00" && $wt['sat_end_time_minute'] == "00") {
        $empty['sat'] = TRANS('OFF_TIME');
    } else {
        $empty['sat'] = TRANS('TIME_FROM') . " " . $wt['sat_ini_time_hour'] . ":" . $wt['sat_ini_time_minute'] . " " . TRANS('TIME_TO') . " " . $wt['sat_end_time_hour'] . ":" . $wt['sat_end_time_minute'];
    }

    if ($wt['sun_ini_time_hour'] == "00" && $wt['sun_ini_time_minute'] == "00" && $wt['sun_end_time_hour'] == "00" && $wt['sun_end_time_minute'] == "00") {
        $empty['sun'] = TRANS('OFF_TIME');
    } else {
        $empty['sun'] = TRANS('TIME_FROM') . " " . $wt['sun_ini_time_hour'] . ":" . $wt['sun_ini_time_minute'] . " " . TRANS('TIME_TO') . " " . $wt['sun_end_time_hour'] . ":" . $wt['sun_end_time_minute'];
    }

    if ($wt['off_ini_time_hour'] == "00" && $wt['off_ini_time_minute'] == "00" && $wt['off_end_time_hour'] == "00" && $wt['off_end_time_minute'] == "00") {
        $empty['off'] = TRANS('OFF_TIME');
    } else {
        $empty['off'] = TRANS('TIME_FROM') . " " . $wt['off_ini_time_hour'] . ":" . $wt['off_ini_time_minute'] . " " . TRANS('TIME_TO') . " " . $wt['off_end_time_hour'] . ":" . $wt['off_end_time_minute'];
    }

    return $empty;

}

/**
 * Retorna um array com os textos do termo de responsabilidade informado
 */
function getStatementsInfo ($conn, $slug): array
{
    $empty = [];
    $empty['header'] = "";
    $empty['title'] = "";
    $empty['p1_bfr_list'] = "";
    $empty['p2_bfr_list'] = "";
    $empty['p3_bfr_list'] = "";
    $empty['p1_aft_list'] = "";
    $empty['p2_aft_list'] = "";
    $empty['p3_aft_list'] = "";
    
    if (empty($slug)) {
        return $empty;
    }
        
    $sql = "SELECT * FROM asset_statements WHERE slug = '{$slug}'";
    try {
        $res = $conn->query($sql);
        if ($res->rowCount())
            return $res->fetch();
        return $empty;
    }
    catch (Exception $e) {
        return $empty;
    }
}


/**
 * Retorna o array com as informações da tabela de Centros de Custos
 * Retorna o array vazio se não localizar o registro
 * Campos de retorno (se não vazio): ccusto_id, ccusto_name, ccusto_cod
 */
function getCostCenterInfo ($conn, $ccId): array
{
    if (empty($ccId)) {
        return [];
    }
    $sql = "SELECT 
                " . CCUSTO_ID . " AS ccusto_id, 
                " . CCUSTO_DESC . " AS ccusto_name, 
                " . CCUSTO_COD . " AS ccusto_cod 
            FROM 
                `" . DB_CCUSTO . "`.`" . TB_CCUSTO . "` 
            WHERE `" . CCUSTO_ID . "` = '{$ccId}' ";
    try {
        $res = $conn->query($sql);
        if ($res->rowCount())
            return $res->fetch();
        return [];
    }
    catch (Exception $e) {
        return [];
    }
}


/**
 * Retorna o array com as informações da tabela de unidades / instituicao
 */
function getUnitInfo ($conn, $unitId): array
{
    $unitId = (int) $unitId;
    
    if (empty($unitId)) {
        return [];
    }
    
    $sql = "SELECT * FROM instituicao WHERE inst_cod = '{$unitId}' ";
    try {
        $res = $conn->query($sql);
        if ($res->rowCount())
            return $res->fetch();
        return [];
    }
    catch (Exception $e) {
        return [];
    }
}



/**
 * Atualiza a informação sobre a data do último logon do usuário
 * @param $conn: conexão PDO
 * @param int $userId: id do usuário
 */
function updateLastLogon ($conn, int $userId): void
{
    $sql = "UPDATE usuarios SET last_logon = '" . date("Y-m-d H:i:s") . "' WHERE user_id = '{$userId}' ";
    try {
        $conn->exec($sql);
    }
    catch (Exception $e) {
        return ;
    }
}

/**
 * Retorna o array com as informações de configuração de e-mail
 */
function getMailConfig ($conn): array
{
    $sql = "SELECT * FROM mailconfig";
    try {
        $res = $conn->query($sql);
        if ($res->rowCount())
            return $res->fetch();
        return [];
    }
    catch (Exception $e) {
        return [];
    }
}
/**
 * Retorna o array com as informações dos templates de mensagens de e-mail para cada evento
 */
function getEventMailConfig ($conn, string $event): array
{
    $sql = "SELECT * FROM msgconfig WHERE msg_event like ('" . $event . "')";
    try {
        $res = $conn->query($sql);
        if ($res->rowCount())
            return $res->fetch();
        return [];
    }
    catch (Exception $e) {
        return [];
    }
}

/**
 * Retorna o array com as informações do status filtrado
 * [stat_id], [status], [stat_cat], [stat_painel], [stat_time_freeze]
 */
function getStatusInfo ($conn, string $statusId): array
{
    $sql = "SELECT * FROM `status` WHERE stat_id = '" . $statusId . "'";
    try {
        $res = $conn->query($sql);
        if ($res->rowCount())
            return $res->fetch();
        return [];
    }
    catch (Exception $e) {
        return [];
    }
}

/**
 * Retorna o array com as informações do usuário e da área de atendimento que ele está vinculado
 * [user_id], [login], [nome], [email], [fone], [nivel], [area_id], [user_admin], [last_logon], 
 * [area_nome], [area_status], [area_email], [area_atende], [sis_screen], [sis_wt_profile],
 * [language]
 * @param $conn: conexao PDO
 * @param int $userId: id do usuário
 * @param string $userName: login do usuário - se for informado, o filtro será por ele
 */
function getUserInfo ($conn, int $userId, string $userName = ''): array
{
    $terms = (empty($userName) ? " user_id = '{$userId}' " : " login = '{$userName}' ");
    $sql = "SELECT 
                u.user_id, 
                u.login, u.nome, 
                u.email, u.fone, 
                u.nivel, u.AREA as area_id, 
                u.user_admin, u.last_logon, 
                a.sistema as area_nome, 
                a.sis_status as area_status, 
                a.sis_email as area_email, 
                a.sis_atende as area_atende, a.sis_screen, 
                a.sis_wt_profile, 
                p.upref_lang as language
            FROM 
                sistemas a, usuarios u LEFT JOIN
                uprefs p ON u.user_id = p.upref_uid
                
            WHERE 
                u.AREA = a.sis_id 
                AND 
                {$terms} ";
    try {
        $res = $conn->query($sql);
        if ($res->rowCount())
            return $res->fetch();
        return [];
    }
    catch (Exception $e) {
        return [];
    }
}


/**
 * Retorna o total de chamados vinculados a um determinado operador
 * @var PDO $conn
 * @var int $userId
 * @return int
 */
function getOperatorTickets ($conn, int $userId): int
{
    $sql = "SELECT 
                count(*) AS total 
            FROM 
                ocorrencias o, `status` s 
            WHERE 
                o.operador = {$userId} AND 
                o.status = s.stat_id AND 
                s.stat_painel = 1  AND 
                o.oco_scheduled = 0
            ";
    try {
        $res = $conn->query($sql);
        if ($res->rowCount())
            return $res->fetch()['total'];
        return 0;
    }
    catch (Exception $e) {
        return 0;
    }
}


/**
 * Retorna uma string com as áreas SECUNDÁRIAS associadas ao usuário
 * @param $conn: conexao PDO
 * @param int $userId: id do usuário
 *
 */
function getUserAreas ($conn, int $userId): string
{
    $areas = "";
    $sql = "SELECT uarea_sid FROM usuarios_areas WHERE uarea_uid = '{$userId}' ";
    
    try {
        $res = $conn->query($sql);
        if ($res->rowCount()) {
            foreach ($res->fetchAll() as $row) {
                if (strlen($areas) > 0)
                    $areas .= ",";
                $areas .= $row['uarea_sid'];
            }
            return $areas;
        }
        return $areas;
    }
    catch (Exception $e) {
        return $areas;
    }
}


/**
 * Retorna o array com as informações do perfil de tela de abertura
 * [conf_cod], [conf_name], [conf_user_opencall - permite autocadastro], [conf_custom_areas], 
 * [conf_ownarea - area para usuários que se autocadastram], [conf_ownarea_2], [conf_opentoarea]
 * [conf_screen_area], []... [conf_screen_msg]
 * 
 */
function getScreenInfo ($conn, int $screenId): array
{
    $sql = "SELECT 
                *
            FROM 
                configusercall
            WHERE 
                conf_cod = '" . $screenId . "' ";
    try {
        $res = $conn->query($sql);
        if ($res->rowCount())
            return $res->fetch();
        return [];
    }
    catch (Exception $e) {
        return [];
    }
}


/**
 * Retorna o array com as informações da área de atendimento:
 * [area_id], [area_name], [status], [email], [atende], [screen], [wt_profile]
 */
function getAreaInfo ($conn, int $areaId): array
{
    $sql = "SELECT 
                sis_id as area_id, 
                sistema as area_name, 
                sis_status as status, 
                sis_email as email, 
                sis_atende as atende, 
                sis_screen as screen, 
                sis_wt_profile as wt_profile 
            FROM 
                sistemas 
            WHERE 
                sis_id = '" . $areaId . "'";
    try {
        $res = $conn->query($sql);
        if ($res->rowCount())
            return $res->fetch();
        return [];
    }
    catch (Exception $e) {
        return [];
    }
}


/**
 * Retorna o array de registros das áreas cadastradas:
 * [sis_id], [sistema], [status], [sis_email], [sis_atende], [sis_screen], [sis_wt_profile]
 * @param int $all 1: todos os registros 1: checará os outros parametros de filtro
 * @param int $status 0: inativas 1: ativas
 * @param int $atende 0: somente abertura 1: atende chamados
 */
function getAreas ($conn, int $all = 1, int $status = 1, int $atende = 1): array
{
    $terms = "";
    if ($all == 0) {
        $terms .= ($status == 1 ? " AND sis_status = 1 " : " AND sis_status = 0 ");
        $terms .= ($atende == 1 ? " AND sis_atende = 1 " : " AND sis_atende = 0 ");
    }
    
    $data = [];
    $sql = "SELECT 
                *
            FROM 
                sistemas 
            WHERE 
                1 = 1 
                {$terms}
            ORDER BY sistema";
    try {
        $res = $conn->query($sql);
        if ($res->rowCount()) {
            foreach ($res->fetchall() as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return [];
    }
    catch (Exception $e) {
        return [];
    }
}


/**
 * Retorna se a área tem permissão de acesso ao módulo do sistema:
 * [perm_area], [perm_modulo]
 * @param $conn: conexão PDO
 * @param int $module - 1: ocorrências - 2: inventário
 * @param $areaId - id da área de atendimento - podem ser várias áreas (secundárias) 
 */
function getModuleAccess ($conn, int $module, $areaId): bool
{
    $sql = "SELECT 
                perm_area, perm_modulo
            FROM 
                permissoes 
            WHERE 
                perm_modulo = '" . $module . "' 
            AND
                perm_area IN ('" . $areaId . "') ";
    try {
        $res = $conn->query($sql);
        if ($res->rowCount())
            return true;
        return false;
    }
    catch (Exception $e) {
        return false;
    }
}


/**
 * Retorna o array de registros dos status cadastradas:
 * [stat_id], [status], [stat_cat], [stat_painel], [stat_time_freeze]
 * @param int $all 1: todos os registros 1: checará os outros parametros de filtro
 * @param string $painel 1: vinculado ao operador, 2: principal  3: oculto
 * @param string $timeFreeze 0: status sem parada 1: status de parada
 * 
 */
function getStatus ($conn, int $all = 1, string $painel = '1,2,3', string $timeFreeze = '0,1'): array
{
    $terms = "";
    if ($all == 0) {
        $terms .= " AND stat_painel in ({$painel}) ";
        $terms .= " AND stat_time_freeze in ({$timeFreeze}) ";
    }
    
    $data = [];
    $sql = "SELECT 
                *
            FROM 
                status 
            WHERE 
                1 = 1 
                {$terms}
            ORDER BY status";
    try {
        $res = $conn->query($sql);
        if ($res->rowCount()) {
            foreach ($res->fetchall() as $row) {
                $data[] = $row;
            }
            return $data;
        }
        return [];
    }
    catch (Exception $e) {
        return [];
    }
}

/**
 * Retorna o array com as informações do último assentamento do chamado:
 * [numero], [ocorrencia], [assentamento], [data], [responsavel], [asset_privated], [tipo_assentamento]
 */
function getLastEntry ($conn, int $ticket): array
{
    $empty = [];
    $empty['numero'] = "";
    $empty['ocorrencia'] = "";
    $empty['assentamento'] = "";
    $empty['data'] = "";
    $empty['responsavel'] = "";
    $empty['asset_privated'] = "";
    $empty['tipo_assentamento'] = "";
    
    $sql = "SELECT 
                * 
            FROM 
                assentamentos 
            WHERE 
                ocorrencia = '{$ticket}' 
                AND
                numero = (SELECT MAX(numero) FROM assentamentos WHERE ocorrencia = '{$ticket}' )
            ";
    $res = $conn->query($sql);
    if ($res->rowCount())
        return $res->fetch();
    return $empty;
}


/**
 * Retorna o array com as informações de descrição técnica e solução para o chamado:
 * [numero], [problema], [solucao], [data], [responsavel]
 */
function getSolutionInfo ($conn, int $ticket): array
{
    $empty = [];
    $empty['numero'] = "";
    $empty['problema'] = "";
    $empty['solucao'] = "";
    $empty['data'] = "";
    $empty['responsavel'] = "";
    
    $sql = "SELECT 
                * 
            FROM 
                solucoes 
            WHERE 
                numero = '{$ticket}' 
            ";
    $res = $conn->query($sql);
    if ($res->rowCount())
        return $res->fetch();
    return $empty;
}




/**
 * Retorna a url de acesso global da ocorrencia
 */
function getGlobalUri ($conn, int $ticket): string
{
    $config = getConfig($conn);

    $sql = "SELECT * FROM global_tickets WHERE gt_ticket = '" . $ticket . "' ";
    $res = $conn->query($sql);
    if ( $res->rowCount() ) {
        $row = $res->fetch();
        return $config['conf_ocomon_site'] . "/ocomon/geral/ticket_show.php?numero=" . $ticket . "&id=" . $row['gt_id'];
    }

    $rand = random64();
    $sql = "INSERT INTO global_tickets (gt_ticket, gt_id) VALUES ({$ticket}, '" . $rand . "')";
    $conn->exec($sql);
    return $config['conf_ocomon_site'] . "/ocomon/geral/ticket_show.php?numero=" . $ticket . "&id=" . $rand;
}


/**
 * Retorna um array com os valores das variáveis de ambiente para serem utilizadas nos templates de envio de e-mail
 */
function getEnvVarsValues ($conn, $ticket): array
{
    include ("../../includes/queries/queries.php");
    
    $config = getConfig($conn);
    $lastEntry = getLastEntry($conn, $ticket);
    $solution = getSolutionInfo($conn, $ticket);

    $sql = $QRY["ocorrencias_full_ini"] . " WHERE o.numero = {$ticket} ";
    $res = $conn->query($sql);
    $row = $res->fetch();

    /* Variáveis de ambiente para os e-mails */
    $vars = array();

    $vars = array();
    $vars['%numero%'] = $row['numero'];
    $vars['%usuario%'] = $row['contato'];
    $vars['%contato%'] = $row['contato'];
    $vars['%contato_email%'] = $row['contato_email'];
    $vars['%descricao%'] = $row['descricao'];
    $vars['%departamento%'] = $row['setor'];
    $vars['%telefone%'] = $row['telefone'];
    $vars['%site%'] = "<a href='" . $config['conf_ocomon_site'] . "'>" . $config['conf_ocomon_site'] . "</a>";
    $vars['%area%'] = $row['area'];
    $vars['%area_email%'] = $row['area_email'];
    $vars['%operador%'] = $row['nome'];
    $vars['%editor%'] = $row['nome'];
    $vars['%aberto_por%'] = $row['aberto_por'];
    $vars['%problema%'] = $row['problema'];
    $vars['%versao%'] = VERSAO;
    $vars['%url%'] = getGlobalUri($conn, $ticket);
    $vars['%linkglobal%'] = $vars['%url%'];

    $vars['%unidade%'] = $row['unidade'];
    $vars['%etiqueta%'] = $row['etiqueta'];
    $vars['%patrimonio%'] = $row['unidade']."&nbsp;".$row['etiqueta'];
    $vars['%data_abertura%'] = dateScreen($row['oco_real_open_date']);
    $vars['%status%'] = $row['chamado_status'];
    $vars['%data_agendamento%'] = (!empty($row['oco_scheduled_to']) ? dateScreen($row['oco_scheduled_to']) : "");
    $vars['%data_fechamento%'] = (!empty($row['data_fechamento']) ? dateScreen($row['data_fechamento']) : "");

    $vars['%dia_agendamento%'] = (!empty($row['oco_scheduled_to']) ? explode(" ", dateScreen($row['oco_scheduled_to']))[0] : "");
    $vars['%hora_agendamento%'] = (!empty($row['oco_scheduled_to']) ? explode(" ", dateScreen($row['oco_scheduled_to']))[1] : "");

    $vars['%descricao_tecnica%'] = $solution['problema'];
    $vars['%solucao%'] = $solution['solucao'];
    $vars['%assentamento%'] = $lastEntry['assentamento'];

    return $vars;
}

/**
 * Retorna o registro gravado com as variáveis de ambiente disponíveis
 */
function getEnvVars ($conn) {
    $sql = "SELECT vars FROM environment_vars";
    try {
        $res = $conn->query($sql);
        return $res->fetch()['vars'];
    }
    catch (Exception $e) {
        return false;
    }
}



/**
 * Realiza substituição dos valores do $index de acordo com o definido no arquivo de idioma utilizado
 * @param string $index: índice do array no arquivo de idioma
 * @param string $suggest: valor que deverá ser criado, caso nao exista, no arquivo de idioma
 * @param int $javascript: faz o escape de quando nao encontra o índice informado 
 *              (necessário para quando esse retorno é passado em um alert do javascript)
 */
function TRANS($index, $suggest = '', $javascript = 0)
{
    /* Para utilizar quando debugando a interface */
    $spanOpening = "<span class='bg-warning text-danger'>";
    if ($javascript)
        $spanOpening = "<span class=\"bg-warning text-danger\">";
    $spanClosing = "</span>";
    $spanOpening = "";
    $spanClosing = "";
    
    if (!isset($_SESSION['s_language'])) {
        $_SESSION['s_language'] = "pt_BR.php";
    }

    if (is_file(__DIR__ . "/" . "../languages/" . $_SESSION['s_language'])) {
        include __DIR__ . "/" . "../languages/" . $_SESSION['s_language'];
    
        if (!isset($TRANS[$index])) {
            if ($javascript) {
                return '<font color=red>$TRANS[\'' . $index . '\']="</font>' . $suggest . '<font color=red>";</font>';
            }
            return '<font color=red>$TRANS[' . $index . ']="</font>' . $suggest . '<font color=red>";</font>';
        } 
        return $spanOpening . $TRANS[$index] . $spanClosing;
    
    }
    return "No translation file found";
}


function dump($variavel, $info = "", $cor = 'magenta')
{
    if (trim($info) != "") {
        echo "<br><font color='" . $cor . "'>" . $info . "</font>";
    }

    if (is_array($variavel) || is_object($variavel)) {
        echo "<pre>";
        print_r($variavel);
        echo "</pre>";
        return;
    }
    
    echo "<pre>";
    echo $variavel;
    echo "</pre>";
    return; 
}

function normaliza($str)
{
    return toHtml($str);
}

function reIndexArray(&$array)
{
    $tmpArray = array();

    if (is_array($array)) {
        $array = array_unique($array);
        foreach ($array as $value) {
            if (!empty($value))
                $tmpArray[] = $value;
        }
        for ($i = 0; $i <= count($array); $i++) {
            array_pop($array);
        }
    }
    $array = $tmpArray;
}



/**
 * Retorna se a combinação de usuário e senha está cadastrada na tabela de usuários
 */
function pass($conn, $user, $pass): bool
{
    $user = filter_var($user, FILTER_SANITIZE_STRIPPED);
    $pass = filter_var($pass, FILTER_SANITIZE_STRIPPED);
    
    $sql = "SELECT 
                user_id 
            FROM 
                usuarios 
            WHERE 
            (
                login = :user 
                AND 
                password = :pass 
                AND
                nivel < 4
            )";
    try {
        
        $result = $conn->prepare($sql);
        $result->bindParam(':user', $user);
        $result->bindParam(':pass', $pass);
        $result->execute();
        
        if ($result->rowCount()) 
            return true;
    }
    catch (Exception $e) {
        return false;
    }
    return false;
}



/** Boostrap and fontAwesome must be included
 * @param string $type - primary|secondary|success|danger|info|warning|light|dark
 * @param string $strong - The short message to be strong bold
 * @param string $message - The message itself
 * @param string $id - the id to be treated in jquery
 * @param string $returnLink - a href link to another page
 * @param bool $fixed - if the message cant be closed
 * @param string $iconFa - specific fontAwesome Class Names
 */
function message($type, $strong, $message, $elementID, $returnLink = '', $fixed = '', $iconFa = ''){

    $fixed = (empty($fixed) ? false : $fixed);

    $icon = [];
    $icon['success'] = "fas fa-check-circle"; 
    $icon['info'] = "fas fa-info-circle"; 
    $icon['warning'] = "fas fa-exclamation-circle";
    $icon['danger'] = "fas fa-exclamation-triangle"; 

    if (!empty($iconFa)) {
        $icon[$type] = $iconFa;
    }

    $goTo = "";
    if (!empty($returnLink)){
        $goTo = "<a href='{$returnLink}' class='alert-link'> - Voltar</a>";
    }
    if (!$fixed)
    /* style=' z-index:1030 !important;' */
        return "
        <div class='d-flex justify-content-center '>
            <div class='d-flex justify-content-center  my-3' style=' width: 95%; position: fixed; top: 1%; z-index:1030 !important;'>
                <div class='alert alert-{$type} alert-dismissible fade show w-100' role='alert' id='{$elementID}'  onClick=\"this.style.display='none'\" >
                    <i class='" . $icon[$type] . "'></i>
                    <strong>{$strong}</strong> {$message} {$goTo}
                    <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                        <span aria-hidden='true'>&times;</span>
                    </button>
                </div>
            </div>
            </div>
        ";
        /* style=' z-index:1030 !important;' */
    return "
        <div class='d-flex justify-content-center' style=' z-index:-1 !important;'>
            <div class='alert alert-{$type} fade show w-100'  role='alert' id='{$elementID}' '>
                <i class='" . $icon[$type] . "'></i>
                <strong>{$strong}</strong> {$message} {$goTo}
                
            </div>
        </div>
    ";
}


function putComma($vetor)
{
    $chamados = "";
    if (is_array($vetor)) {

        if (count($vetor) >= 1) {
            for ($i = 0; $i < count($vetor); $i++) {
                $chamados .= "$vetor[$i],";
            }
            if (strlen($chamados) > 0) {
                $chamados = substr($chamados, 0, -1);
            }
        } 
        return $chamados;
    } 
    return $vetor;
}


/* utilizado no módulo de inventário - retorna a diferença em dias cheios */
function date_diff_dias($data1, $data2)
{
    if (empty($data1) || empty($data2)) {
        return "";
    } 

    $seconds = strtotime($data2) - strtotime($data1);
    $days = intval($seconds / 86400);
    $seconds -= $days * 86400;
    $hours = intval($seconds / 3600);
    $seconds -= $hours * 3600;
    $minutes = intval($seconds / 60);
    $seconds -= $minutes * 60;

    $value = $days;
    return $value;
}



/**
 * Utilizar sempre para gravar no banco
 * @param string|null $date
 * @param string $format
 * @param int|null $nullable (se for 1 então o retorno será vazio caso a data esteja vazia - 
 *                  Se for 0 então o retorno será a data atual caso a data esteja vazia)
 * @return string
 */
function dateDB(?string $date, ?int $nullable = 0): string
{
    $date = (empty($date) ? '' : $date);

    if ($nullable == 0) {
        $date = (empty($date) ? "now" : $date);
    }
    
    if (empty($date)) {
        return '';
    }

    if (strpos($date, '/')) {
        $date = str_replace('/', '-', $date);
    }
    return (new DateTime($date))->format("Y-m-d H:i:s");
}

/**
 * Formata de acordo com o definido no menu de administração
 * @param string|null $date
 * @param int|null $hideTime
 * @return string
 */
function dateScreen(?string $date, ?int $hideTime = 0 ): string
{
    
    $format = 'd/m/Y H:i:s';
    if (isset($_SESSION['s_date_format']) && !empty($_SESSION['s_date_format'])) {
        $format = $_SESSION['s_date_format'];
    }
    if (empty($date))
    return '';

    if ($hideTime != 0) {
        $dateParts = explode(' ', (new DateTime($date))->format($format));
        return $dateParts[0];
    }
    
    return (new DateTime($date))->format($format);
}

/**
 * Apenas repliquei a função dateScreen para não precisar substituir em todos os arquivos que utilizam a formatDate
 */
function formatDate(?string $date, $hideTime = 0): string
{
    $format = 'd/m/Y H:i:s';
    if (isset($_SESSION['s_date_format']) && !empty($_SESSION['s_date_format'])) {
        $format = $_SESSION['s_date_format'];
    }
    if (empty($date))
    return '';

    if ($hideTime != 0 && $hideTime != " ") {
        $dateParts = explode(' ', (new DateTime($date))->format($format));
        return $dateParts[0];
    }
    
    return (new DateTime($date))->format($format);
}


function noHtml($string)
{
    return trim(filter_var($string, FILTER_SANITIZE_STRIPPED));
}

function toHtml($string)
{
    $transTbl = get_html_translation_table(HTML_ENTITIES);
    $transTbl = array_flip($transTbl);
    return strtr($string, $transTbl);
}

function isIn($pattern, $values)
{
    if (strpos($values, ",")) {
        $valuesArray = explode(",", $values);

        for ($i = 0; $i < count($valuesArray); $i++) {
            if ($valuesArray[$i] == (int) $pattern) {
                return true;
            }
        }
    } 
    
    if ($values == (int) $pattern) {
        return true;
    }
    return false;
}

function sepComma($value, $array)
{
    $array = $value;
    
    if (strpos($value, ",")) {
        $array = explode(",", $value);
    }

    return (array)$array;
}

function random()
{
    $rand = "";
    for ($i = 0; $i < 10; $i++) {
        $rand .= mt_rand(1, 300);
    }
    return ($rand);
}

function random64() {
    return base64_encode(random_bytes(20));
}

function transbool($bool)
{
    if ($bool == 0) {
        return TRANS('NOT');
    }
    if ($bool == 1) {
        return TRANS('YES');
    }
    return $bool;
}

function transvars($msg, $arrayEnv)
{
    foreach ($arrayEnv as $id => $var) {
        $msg = str_replace($id, $var, $msg);
    }
    return $msg;
}

/**
 * Realiza o envio de e-mails automáticos
 */
function send_mail($event, $e_destino, $mailConf, $msgConf, $envVars, $attach = '')
{

    //$event: Tipo de evento, os eventos são definidos pela situação (abertura, edição ou assentamento)
    //e pelo destino (usuário, operador ou área)
    //$e_destino: e-mail de destino
    //$mailConf: array com as informações de conexão smtp
    //$msgConf: array com as informações de mensagem
    //$envVar: array com as variáveis de ambiente

    if (!$mailConf['mail_send']) {
        return true;
    }    

    if (is_file("./.root_dir")) {

        if (!class_exists(PHPMailer::class)) {
            require __DIR__ . "/../components/PHPMailer-master/src/Exception.php";
            require __DIR__ . "/../components/PHPMailer-master/src/PHPMailer.php";
            require __DIR__ . "/../components/PHPMailer-master/src/SMTP.php";
        }

    } else {

        if (!class_exists(PHPMailer::class)) {
            require __DIR__ . "/../components/PHPMailer-master/src/Exception.php";
            require __DIR__ . "/../components/PHPMailer-master/src/PHPMailer.php";
            require __DIR__ . "/../components/PHPMailer-master/src/SMTP.php";
        }
    }

    $mail = new PHPMailer;
    //Tell PHPMailer to use SMTP
    // $mail->isSMTP();

    if ($mailConf['mail_issmtp']) {
        $mail->IsSMTP();
    } // set mailer to use SMTP
    

    //Enable SMTP debugging
    // 0 = off (for production use)
    // 1 = client messages
    // 2 = client and server messages
    $mail->SMTPDebug = 0;
    //Set the hostname of the mail server
    //$mail->Host = 'smtp.office365.com';
    $mail->Host = $mailConf['mail_host']; // specify main and backup server
    // use
    // $mail->Host = gethostbyname('smtp.gmail.com');
    // if your network does not support SMTP over IPv6
    //Set the SMTP port number - 587 for authenticated TLS, a.k.a. RFC4409 SMTP submission
    // $mail->Port = 587;
    $mail->Port = $mailConf['mail_port'];
    //Set the encryption system to use - ssl (deprecated) or tls
    // $mail->SMTPSecure = 'tls';
    $mail->SMTPSecure = $mailConf['mail_secure'];
    //Whether to use SMTP authentication
    //$mail->SMTPAuth = true;
    $mail->SMTPAuth = $mailConf['mail_isauth']; // turn on SMTP authentication

    $mail->CharSet = 'UTF-8';
    $mail->setLanguage = 'br';
    $mail->Encoding = 'base64';

    //Username to use for SMTP authentication - use full email address for gmail
    $mail->Username = $mailConf['mail_user']; // SMTP username
    //Password to use for SMTP authentication
    $mail->Password = $mailConf['mail_pass']; // SMTP password
    //Set who the message is to be sent from
    $mail->setFrom($mailConf['mail_from'], $msgConf['msg_fromname']);
    //Set an alternative reply-to address
    //Set who the message is to be sent to

    $recipients = 1;
    $sepTo = explode(",", $e_destino);
    if (is_array($sepTo)) {
        $recipients = count($sepTo);
    }

    for ($i = 0; $i < $recipients; $i++) {
        $mail->addAddress(trim($sepTo[$i]));
    }

    //Set the subject line
    //$mail->Subject = 'MENSAGEM DE TESTE DO PHPMAILER';
    $mail->Subject = transvars($msgConf['msg_subject'], $envVars);
    //Read an HTML message body from an external file, convert referenced images to embedded,
    //convert HTML into a basic plain-text alternative body

    //$mail->msgHTML(file_get_contents('contents.html'), __DIR__);
    // $mail->msgHTML(transvars($msgConf['msg_body'], $envVars));
    $mail->msgHTML(nl2br(transvars($msgConf['msg_body'], $envVars)));
    //Replace the plain text body with one created manually
    //$mail->AltBody = 'This is a plain-text message body';
    $mail->AltBody = nl2br(transvars($msgConf['msg_altbody'], $envVars));
    //Attach an image file
    //$mail->addAttachment('images/phpmailer_mini.png');
    if (!empty($attach)) {
        $mail->addAttachment($attach);
    }

    //send the message, check for errors
    if (!$mail->send()) {
        // echo "Mailer Error: " . $mail->ErrorInfo;
        return false;
        // exit();
    } 

    return true;
}

/**
 * Faz o envio dos e-mails manuais
 */
function mail_send($mailConf, $to, $cc, $subject, $body, $replyto, $envVars)
{

    //$mailConf: array com as informações de conexão smtp

    if (!$mailConf['mail_send']) {
        return true;
    }    

    if (is_file("./.root_dir")) {
        if (!class_exists(PHPMailer::class)) {
            require __DIR__ . "/../components/PHPMailer-master/src/Exception.php";
            require __DIR__ . "/../components/PHPMailer-master/src/PHPMailer.php";
            require __DIR__ . "/../components/PHPMailer-master/src/SMTP.php";
        }
    } else {
        if (!class_exists(PHPMailer::class)) {
            require __DIR__ . "/../components/PHPMailer-master/src/Exception.php";
            require __DIR__ . "/../components/PHPMailer-master/src/PHPMailer.php";
            require __DIR__ . "/../components/PHPMailer-master/src/SMTP.php";
        }
    }
    
    $mail = new PHPMailer;
    //Tell PHPMailer to use SMTP
    //$mail->isSMTP();
    if ($mailConf['mail_issmtp']) {
        $mail->IsSMTP();
    }

    //Enable SMTP debugging
    // 0 = off (for production use)
    // 1 = client messages
    // 2 = client and server messages
    $mail->SMTPDebug = 0;
    //Set the hostname of the mail server
    //$mail->Host = 'smtp.office365.com';
    $mail->Host = $mailConf['mail_host']; // specify main and backup server
    // use
    // $mail->Host = gethostbyname('smtp.gmail.com');
    // if your network does not support SMTP over IPv6
    //Set the SMTP port number - 587 for authenticated TLS, a.k.a. RFC4409 SMTP submission
    // $mail->Port = 587;
    $mail->Port = $mailConf['mail_port'];
    //Set the encryption system to use - ssl (deprecated) or tls
    // $mail->SMTPSecure = 'tls';
    $mail->SMTPSecure = $mailConf['mail_secure'];
    //Whether to use SMTP authentication
    //$mail->SMTPAuth = true;
    $mail->SMTPAuth = $mailConf['mail_isauth']; // turn on SMTP authentication

    $mail->CharSet = 'UTF-8';
    $mail->setLanguage = 'br';
    $mail->Encoding = 'base64';

    //Username to use for SMTP authentication - use full email address for gmail
    $mail->Username = $mailConf['mail_user']; // SMTP username
    //Password to use for SMTP authentication
    $mail->Password = $mailConf['mail_pass']; // SMTP password
    //Set who the message is to be sent from
    $mail->setFrom($mailConf['mail_from'], $mailConf['mail_from_name']);
    //Set an alternative reply-to address
    //Set who the message is to be sent to

    $mail->AddReplyTo($replyto, $mailConf['mail_from_name']);

    //$mail->msgHTML(file_get_contents('contents.html'), __DIR__);
    // $mail->msgHTML(transvars($msgConf['msg_body'],$envVars));
    $mail->msgHTML(nl2br(transvars($body, $envVars)));

    //Replace the plain text body with one created manually
    //$mail->AltBody = 'This is a plain-text message body';
    // $mail->AltBody = nl2br(transvars($msgConf['msg_altbody'],$envVars));
    $mail->AltBody = nl2br(transvars($body, $envVars));

    //     $mail->AddAddress($to);
    //     if (isset($cc) && $cc!=""){
    //         $mail->AddCC($cc);
    //     }

    $recipients = 1;
    $sepTo = explode(",", $to);
    if (is_array($sepTo)) {
        $recipients = count($sepTo);
    }

    for ($i = 0; $i < $recipients; $i++) {
        $mail->AddAddress(trim($sepTo[$i]));
    }

    if (isset($cc) && $cc != "") {

        $sepCC = explode(",", $cc);

        $copies = 1;
        if (is_array($sepCC)) {
            $copies = count($sepCC);
        }

        for ($i = 0; $i < $copies; $i++) {
            $mail->AddCC(trim($sepCC[$i]));
        }
    }

    $mail->Subject = transvars($subject, $envVars);

    ## TO USE ONLY FOR DEVELOPERS WHEN DEBUGING
    // dump ($mailConf['mail_issmtp'],'IsSMTP');
    // dump ($mail->Host,'Host');
    // dump ($mail->SMTPAuth,'SMTPAuth');
    // dump ($mail->Username,'Username');
    // dump ($mail->Password,'Password');
    // dump ($mail->From,'From');
    // dump ($mailConf['mail_ishtml'],'IsHTML');
    // dump ($mail->FromName,'FromName');
    // dump ($to,'AddAddress');
    // dump ($msgConf['msg_replyto'],'AddReplyTo');
    // dump ($mail->Subject,'Subject');
    // dump ($mail->Body,'Body');
    // dump ($mail->AltBody,'AltBody');
    // exit;

    if (!$mail->Send()) {
        echo "A mensagem não pôde ser enviada. <p>";
        echo "Mailer Error: " . $mail->ErrorInfo;
        // exit;
        return false;
    }

    return true;
}


function isImage($type)
{
    if (eregi("^image\/(pjpeg|jpeg|png|gif|x-ms-bmp)$", $type)) {
        return true;
    }
    return false;
}

function noSpace($word)
{
    $newWord = trim(str_replace(" ", "_", $word));
    return $newWord;
}

/**
 * Realiza a validação dos tipos de arquivos permitidos de acordo com o mimetype e tamanho
 */
function upload($imgFile, $config, $fileTypes = "%%IMG%", $fileAttributes = "")
{

    include __DIR__ . "/" . "../languages/" . $_SESSION['s_language'];

    if (empty($fileAttributes))
        $fileAttributes = $_FILES[$imgFile];
    $arquivo = ($_FILES && isset($_FILES[$imgFile]) ? $fileAttributes : false);

    $maxFileSize = ($config["conf_upld_size"] / 1024) . "kbytes";
    $saida = "OK";

    if ($arquivo) {
        
        if ($arquivo['error'] == 2) {
            return TRANS('FILE_TOO_HEAVY') . ". " . TRANS('LIMIT') . ": " . $maxFileSize;
        }
        
        $erro = array();
        $mime = array();
        $type = explode("%", $fileTypes);
        reIndexArray($type);

        /* A serem testados de acordo com o permitido na configuração geral */
        $mime['PDF'] = "application\/pdf";
        $mime['TXT'] = "text\/plain";
        $mime['RTF'] = "application\/rtf";
        $mime['HTML'] = "text\/html";
        $mime['IMG'] = "image\/(pjpeg|jpeg|png|gif|x-ms-bmp)";
        $mime['ODF'] = "application\/vnd.oasis.opendocument.(text|spreadsheet|presentation|graphics)";
        $mime['OOO'] = "application\/vnd.sun.xml.(writer|calc|draw|impress)";
        $mime['MSO'] = "application\/(msword|vnd.ms-excel|vnd.ms-powerpoint)";
        $mime['NMSO'] = "application\/vnd.openxmlformats-officedocument.(wordprocessingml.document|spreadsheetml.sheet|presentationml.presentation|presentationml.slideshow)";

        $typeOK = false;
        $types = "";
        for ($i = 0; $i < count($type); $i++) {
            if (strlen($types) > 0) {
                $types .= ", ";
            }
            if ($type[$i] == "IMG") {
                $types .= "jpeg, png, gif, bmp";
            } else
            if ($type[$i] == "PDF") {
                $types .= "pdf";
            } else
            if ($type[$i] == "TXT") {
                $types .= "txt";
            } else
            if ($type[$i] == "RTF") {
                $types .= "rtf";
            } else
            if ($type[$i] == "HTML") {
                $types .= "html";
            } else
            if ($type[$i] == "ODF") {
                $types .= "odt, ods, odp, odg";
            } else
            if ($type[$i] == "OOO") {
                $types .= "sxw, sxc, sxi, sxd";
            } else
            if ($type[$i] == "MSO") {
                $types .= "doc, xls, ppt";
            } else
            if ($type[$i] == "NMSO") {
                $types .= "docx, xlsx, pptx, ppsx";
            }

            if (preg_match("/^" . $mime[$type[$i]] . "$/i", $arquivo["type"])) {
                $typeOK = true;
            }
        }

        if (!$typeOK) {
            $erro[] = TRANS('UPLOAD_TYPE_NOT_ALLOWED') . $types;
        } else {
            // Verifica tamanho do arquivo
            if ($arquivo["size"] >= $config["conf_upld_size"]) {
                $erro[] = TRANS('FILE_TOO_HEAVY') . ". " . TRANS('LIMIT') . ": " . $maxFileSize;
            } else
            
            if (preg_match("/^image\/(pjpeg|jpeg|png|gif|bmp)$/i", $arquivo["type"])) {
                // Se for imagem
                $tamanhos = getimagesize($arquivo["tmp_name"]);
                // Verifica largura
                if ($tamanhos[0] > $config["conf_upld_width"]) {
                    $erro[] = TRANS('WIDTH_TOO_LARGE') . " " . $config["conf_upld_width"] . " pixels";
                }
                // Verifica altura
                if ($tamanhos[1] > $config["conf_upld_height"]) {
                    $erro[] = TRANS('HEIGHT_TOO_LARGE') . " " . $config["conf_upld_height"] . " pixels";
                }
            }
        }
        if (sizeof($erro)) {
            $saida = "";
            foreach ($erro as $err) {
                $saida .= "<hr>" . $err;
            }
        }
        if ($arquivo && !sizeof($erro)) {
            $saida = "OK";
        }
    } else {
        $saida = "File error!";
    }
    return $saida;
}


//Destaca as entradas '$string' em um texto '$texto' passado
function destaca($string, $texto)
{
    $string .= "|" . noHtml($string) . "|" . toHtml($string);

    $pattern = explode("|", $string);
    $pattern = array_unique($pattern);
    $destaque = array();

    reIndexArray($pattern);

    $texto2 = toHtml(strtolower($texto));

    for ($i = 0; $i < count($pattern); $i++) {
        $destaque = "<mark><span class='text-dark bg-warning p-1'>" . $pattern[$i] . "</span></mark>";
        $texto2 = str_replace(strtolower($pattern[$i]), strtolower($destaque), $texto2);
    }
    return $texto2;
}


/**
 * Realiza a validação por expressão regular
 * @param string $CAMPO: label/rótulo do campo - será utilizado para indicar ao usuário qual é o campo
 * @param mix $VALOR: O valor a ser validado
 * @param string $TIPO: O tipo para o qual o valor será verificado - ver a listagem possível
 * @param string $ERR: Variável que recebe a Mensagem de retorno por referência 
 * @param string $MSG: Mensagem de retorno personalizada
 */
function valida($campo, $valor, $tipo, $obrigatorio, &$err, $msg = '')
{

    include __DIR__ . "/" . "../languages/" . $_SESSION['s_language'];

    $LISTA = array();
    $LISTA['INTFULL'] = "/^\d*$/"; //INTEIRO QUALQUER
    $LISTA['INTEIRO'] = "/^[1-9]\d*$/"; //NAO INICIADOS POR ZERO
    $LISTA['MAIL'] = "/^[\w!#$%&'*+\/=?^`{|}~-]+(\.[\w!#$%&'*+\/=?^`{|}~-]+)*@(([\w-]+\.)+[A-Za-z]{2,6}|\[\d{1,3}(\.\d{1,3}){3}\])$/";
    $LISTA['MAILMULTI'] = "/^([\w!#$%&'*+\/=?^`{|}~-]+(\.[\w!#$%&'*+\/=?^`{|}~-]+)*@(([\w-]+\.)+[A-Za-z]{2,6}|\[\d{1,3}(\.\d{1,3}){3}\]))(\,\s?([\w!#$%&'*+\/=?^`{|}~-]+(\.[\w!#$%&'*+\/=?^`{|}~-]+)*@(([\w-]+\.)+[A-Za-z]{2,6}|\[\d{1,3}(\.\d{1,3}){3}\]))+)*$/";
    $LISTA['DATA'] = "/^((0?[1-9]|[12]\d)\/(0?[1-9]|1[0-2])|30\/(0?[13-9]|1[0-2])|31\/(0?[13578]|1[02]))\/(19|20)?\d{2}$/";
    $LISTA['DATA_'] = "/^((0?[1-9]|[12]\d)\-(0?[1-9]|1[0-2])|30\-(0?[13-9]|1[0-2])|31\-(0?[13578]|1[02]))\-(19|20)?\d{2}$/";
    $LISTA['DATAHORA'] = "/^(((0?[1-9]|[12]\d)\/(0?[1-9]|1[0-2])|30\/(0?[13-9]|1[0-2])|31\/(0?[13578]|1[02]))\/(19|20)?\d{2})[ ]([0-1]\d|2[0-3])+:[0-5]\d:[0-5]\d$/";
    $LISTA['MOEDA'] = "/^\d{1,3}(\.\d{3})*\,\d{2}$/";
    $LISTA['MOEDASIMP'] = "/^\d*\,\d{2}$/";
    $LISTA['ETIQUETA'] = "/^[1-9]\d*(\,\d+)*$/"; //expressão para validar consultas separadas por vírgula;
    $LISTA['ALFA'] = "/^[A-Z]|[a-z]([A-Z]|[a-z])*$/";
    $LISTA['ALFANUM'] = "/^([A-Z]|[a-z]|[0-9])([A-Z]|[a-z]|[0-9])*\.?([A-Z]|[a-z]|[0-9])([A-Z]|[a-z]|[0-9])*$/"; //Valores alfanuméricos aceitando separação com no máximo um ponto.
    $LISTA['ALFAFULL'] = "/^[\w!#$%&'*+\/=?^`{|}~-]+(\.[\w!#$%&'*+\/=?^`{|}~-]+)*$/";
    $LISTA['FONE'] = "/^(([+][\d]{2,2})?([-]|[\s])?[\d]*([-]|[\s])?[\d]+)+([,][\s]([+][\d]{2,2})?([-]|[\s])?[\d]*([-]|[\s])?[\d]+)*$/";
    $LISTA['COR'] = "/^([#]([A-F]|[a-f]|[\d]){6,6})|([I][M][G][_][D][E][F][A][U][L][T])$/";
    $LISTA['USUARIO'] = "/^([0-9a-zA-Z]+([_.-]?[0-9a-zA-Z]+))$/";

    $LISTA['ANO'] = "/^\d{4}$/"; //var regANO = /^\d{4}$/;

    $ERRO = array();
    $ERRO['OBRIGATORIO'] = "O campo " . $campo . " é obrigatório!";
    $ERRO['INTFULL'] = "O campo " . $campo . " deve conter apenas numeros inteiros!";
    $ERRO['INTEIRO'] = "O campo " . $campo . " deve conter apenas numeros inteiros não iniciados por ZERO!";
    $ERRO['MAIL'] = "Formato de e-mail inválido para o campo {$campo}";
    $ERRO['MAILMULTI'] = TRANS('INVALID_EMAIL_FORMAT');
    $ERRO['DATA'] = "Formato de data invalido! dd/mm/aaaa";
    $ERRO['DATA_'] = "Formato de data invalido! dd-mm-aaaa";
    $ERRO['DATAHORA'] = "Formato de data invalido! dd/mm/aaaa H:m:s";
    $ERRO['MOEDA'] = "Formato de moeda inválido!";
    $ERRO['MOEDASIMP'] = "Formato de moeda inválido! XXXXXX,XX";
    $ERRO['ETIQUETA'] = "o Formato do campo " . $campo . " deve ser de valores inteiros não iniciados por Zero e separados por vírgula!";
    $ERRO['ALFA'] = "Esse o campo " . $campo . " só aceita carateres do alfabeto sem espaços!";
    $ERRO['ALFANUM'] = "O campo " . $campo . " só aceita valores alfanuméricos sem espaços ou separados por um ponto(no máximo um)!";
    $ERRO['ALFAFULL'] = "O campo " . $campo . " só aceita valores alfanuméricos sem espaços!";
    $ERRO['FONE'] = "O campo " . $campo . " só aceita valores formatados para telefones (algarismos, traços e espaços) separados por vírgula.";
    $ERRO['COR'] = "O campo " . $campo . " só aceita valores formatados para cores HTML! Ex: #FFCC99";
    $ERRO['USUARIO'] = "O campo " . $campo . " não está no formato aceito.";
    $ERRO['ANO'] = "O campo " . $campo . " não está no formato aceito.";

    if ($LISTA[$tipo] == '') {
        print "ÍNDICE INVÁLIDO!";
        return false;
    }
    
    if ($obrigatorio) {
        if ($valor == '') {
            $err = ($msg == "") ? $ERRO['OBRIGATORIO'] : $msg;
            return false;
        }
        if (preg_match($LISTA[$tipo], $valor)) {
            return true;
        } 
        $err = ($msg == "") ? $ERRO[$tipo] : $msg;
        return false;
    }
    
    if ($valor != '') {
        if (preg_match($LISTA[$tipo], $valor)) {
            return true;
        }

        $err = ($msg == "") ? $ERRO[$tipo] : $msg;
        return false;
    }
    return true;
}

function getDirFileNames($dir, $ext = 'php|PHP')
{
    // Abre um diretorio conhecido, e faz a leitura de seu conteudo de acordo com a extensão solicitada
    $array = array();
    if (is_dir($dir)) {
        if ($readFiles = opendir($dir)) {
            while (($file = readdir($readFiles)) !== false) {
                if ($file != '..' && $file != '.' && $file != '' && $file != 'index.php') {
                    if (eregi("\.(" . $ext . "){1}$", $file)) {
                        $array[] = $file;
                    }
                }
            }
            closedir($readFiles);
        }
    }
    return $array;
}

function isPar($number){ 
    if($number % 2 == 0){ 
        return true;
    } 
    return false;
} 

function isImpar($number){ 
    if($number % 2 == 0){ 
        return false;
    } 
    return true;
} 


/**
 * dbField
 *
 * @param mixed $field
 * @param mixed $type="int"|"text"|"date"|"float"
 * 
 * @return [type]
 */
function dbField($field, $type="int") {

    $field = noHtml($field);

    if ($type == "int")
        return $field = ($field == '-1' || $field == 'null' || $field == '' ? 'null' : $field);

    if ($type == "float") {
        $field = str_replace(',','.', $field);
        return $field = ($field == '-1' || $field == 'null' || $field == '' ? 'null' : $field);
    }
        
    if ($type == "text")
        return $field = ($field == '-1' || $field == 'null' || $field == '' ? 'null' : "'$field'");

    if ($type == "date") {
        // $field = FDate($field);
        $field = dateDB($field, 1);
        return $field = ($field == '' || $field == '0000-00-00' || $field == '0000-00-00 00:00:00' ? 'null' : "'$field'");
    }
}



/**
 * csrf Token
 *
 * @return void
 */
function csrf(): void
{
    $_SESSION['csrf_token'] = base64_encode(random_bytes(20));
}

/**
 * csrf_input
 *
 * @return string
 */
function csrf_input(): string
{
    csrf();
    return "<input type='hidden' name='csrf' id='csrf' value='".($_SESSION['csrf_token'] ?? "")."'/>";
}

/**
 * csrf_verify
 *
 * @param $request
 * 
 * @return bool
 */
function csrf_verify($request): bool
{
    if (empty($_SESSION['csrf_token']) || empty($request['csrf']) || $request['csrf'] != $_SESSION['csrf_token']){
        return false;
    }
    csrf();
    return true;
}

/**
 * @param string $url
 */
function redirect(string $url): void
{
    header("HTTP/1.1 302 Redirect");
    // if (filter_var($url, FILTER_VALIDATE_URL)) {
        header("Location: {$url}");
        exit();
    // }
}

/** 
 * Realiza a inserção das informações de período de tempo para o chamado
 * $conn: conexão
 * $ticket: número do chamado
 * $stage_type: start|stop
 * $tk_status: status do chamado - só será gravado quando o $stage_type for 'start'
 * $specificDate: data específica para gravar - para os casos de chamados saindo 
 *  da fila de agendamento por meio de processos automatizados
*/
function insert_ticket_stage ($conn, $ticket, $stageType,  $tkStatus, $specificDate = '') {

    $date = (!empty($specificDate) ? $specificDate : date("Y-m-d H:i:s"));
    
    $sqlTkt = "SELECT * FROM `tickets_stages` 
                WHERE ticket = {$ticket} AND id = (SELECT max(id) FROM tickets_stages WHERE ticket = {$ticket}) ";
    $resultTkt = $conn->query($sqlTkt);
    $recordsTkt = $resultTkt->rowCount();

    /* Nenhum registro do chamado na tabela. Nesse caso posso apenas inserir um novo */
    if (!$recordsTkt && $stageType == 'start') {
        
        $sql = "INSERT INTO tickets_stages (id, ticket, date_start, status_id) 
        values (null, {$ticket}, '" . $date . "', {$tkStatus}) ";
    
    } elseif (!$recordsTkt && $stageType == 'stop') {
        
        /* Para chamados existentes anteriormente à implementação da tickets_stages */
        $sqlDateTicket = "SELECT data_abertura, oco_real_open_date FROM ocorrencias WHERE numero = {$ticket} ";
        $resDateTicket = $conn->query($sqlDateTicket);

        $rowDateTicket = $resDateTicket->fetch();

        $openDate = $rowDateTicket['data_abertura'];
        $realOpenDate = $rowDateTicket['oco_real_open_date'];

        $recordDate = (!empty($realOpenDate) ? $realOpenDate : $openDate);

        /* Chamado já existia - nesse caso adiciono um período de start e stop com data de abertura registrada para o chamado*/
        /* o Status zero será para identificar que o período foi inserido nessa condição especial */
        $sql = "INSERT INTO tickets_stages (id, ticket, date_start, date_stop, status_id) 
        values (null, {$ticket}, '" . $recordDate . "', '" . $date . "', 0) ";
        try {
            $conn->exec($sql);
        }
        catch (Exception $e) {
            return false;
        }
        
        //Não posso iniciar um estágio de tempo sem ter primeiro um registro de 'start'
        // return false;
        return true;
    }

    /* Já há registro para esse chamado na tabela de estágios de tempo */
    if ($recordsTkt) {
        $row = $resultTkt->fetch();

        /* há uma data de parada no último registro */
        if (!empty($row['date_stop'])) {
            /* Então preciso inserir novo registro de start */
            if ($stageType == 'start') {
                $sql = "INSERT INTO tickets_stages (id, ticket, date_start, status_id) 
                        values (null, {$ticket}, '" . $date . "', {$tkStatus}) ";
            } elseif ($stageType == 'stop') {
                return false;
            }
        } else {
            /* Preciso atualizar o registro com a parada (stop) */
            if ($stageType == 'stop') {
                $sql = "UPDATE tickets_stages SET date_stop = '" . $date . "' WHERE id = " . $row['id'] . " ";
            } elseif ($stageType == 'start') {
                return false;
            }
        }
    }
    try {
        $conn->exec($sql);
    }
    catch (Exception $e) {
        return false;
    }

    return true;
}

/**
 * Retorna o código do nível do usuário que abriu o chamado
 */
function getOpenerLevel ($conn, int $ticket): int
{
    $sql = "SELECT u.nivel FROM usuarios u, ocorrencias o WHERE o.numero = {$ticket} AND o.aberto_por = u.user_id ";
    $result = $conn->query($sql);

    return $result->fetch()['nivel'];
}

/**
 * Retorna o endereço de e-mail do usuário que abriu o chamado
 */
function getOpenerEmail ($conn, int $ticket): string
{
    $sql = "SELECT u.email FROM usuarios u, ocorrencias o WHERE o.numero = {$ticket} AND o.aberto_por = u.user_id ";
    $result = $conn->query($sql);

    return $result->fetch()['email'];
}


/**
 * Retorna o nome do mês correspondente ao índice numérico recebido - valores de 1 a 12
 */
function getMonthLabel($monthIndex){

    include __DIR__ . "/" . "../languages/" . $_SESSION['s_language'];

    $months = array();

    $months[1] = TRANS('JANUARY');
    $months[2] = TRANS('FEBRUARY');
    $months[3] = TRANS('MARCH');
    $months[4] = TRANS('APRIL');
    $months[5] = TRANS('MAY');
    $months[6] = TRANS('JUNE');
    $months[7] = TRANS('JULY');
    $months[8] = TRANS('AUGUST');
    $months[9] = TRANS('SEPTEMBER');
    $months[10] = TRANS('OCTOBER');
    $months[11] = TRANS('NOVEMBER');
    $months[12] = TRANS('DECEMBER');

    return $months[$monthIndex];
}


/**
 * Retorna o nome do tipo de assentamento de acordo com o indice informado 
 */
function getEntryType($entryIndex){

    include __DIR__ . "/" . "../languages/" . $_SESSION['s_language'];

    $types = array();

    $types[0] = TRANS('ENTRY_TYPE_OPENING');
    $types[1] = TRANS('ENTRY_TYPE_EDITING');
    $types[2] = TRANS('ENTRY_TYPE_GET_TO_TREAT');
    $types[3] = TRANS('ENTRY_TYPE_JUSTIFYING');
    $types[4] = TRANS('ENTRY_TYPE_TECH_DESCRIPTION');
    $types[5] = TRANS('ENTRY_TYPE_SOLUTION_DESCRIPTION');
    $types[6] = TRANS('ENTRY_TYPE_OUT_OF_SCHEDULE');
    $types[7] = TRANS('ENTRY_TYPE_SCHEDULING');
    $types[8] = TRANS('ENTRY_TYPE_ADDITIONAL_INFO');
    $types[9] = TRANS('ENTRY_TYPE_TICKET_REOPENED');
    $types[10] = TRANS('ENTRY_TYPE_SUBTICKET_OPENED');
    $types[11] = TRANS('ENTRY_TYPE_TICKET_RELATION_REMOVED');

    if (!array_key_exists($entryIndex, $types)) {
        return TRANS('ENTRY_TYPE_NOT_LABELED');
    }
    return $types[$entryIndex];
}

/**
 * Retorna o nome do tipo de operação para log, de acordo com o indice informado 
 */
function getOperationType($index){

    include __DIR__ . "/" . "../languages/" . $_SESSION['s_language'];

    $types = array();

    $types[0] = TRANS('OPT_OPERATION_TYPE_OPEN');
    $types[1] = TRANS('OPT_OPERATION_TYPE_EDIT');
    $types[2] = TRANS('OPT_OPERATION_TYPE_ATTEND');
    $types[3] = TRANS('OPT_OPERATION_TYPE_REOPEN');
    $types[4] = TRANS('OPT_OPERATION_TYPE_CLOSE');
    $types[5] = TRANS('OPT_OPERATION_TYPE_ATTRIB');
    $types[6] = TRANS('OPT_OPERATION_SCHEDULE');

    if (!array_key_exists($index, $types)) {
        return TRANS('OPT_OPERATION_NOT_LABELED');
    }
    return $types[$index];
}


/**
 * Retorna um array com as datas de início e fim de cada mês 
 * no perído retroativo compatível com o parâmetro de intervalo informado: ex: P6M
 */
function getMonthRangesUpToNOw($maxInterval) {
    // $maxInterval = 'P6M';
    $regularInterval = 'P1M';

    $begin = new DateTime(date('Y-m-01 00:00:00'));
    $begin = date_sub($begin, new DateInterval($maxInterval));
    $end = new DateTime(date('Y-m-01 00:00:00'));
    $end = date_add($end, new DateInterval($regularInterval));

    $interval = new DateInterval($regularInterval);
    $daterange = new DatePeriod($begin, $interval ,$end);
    $dates = [];
    foreach($daterange as $date){
        $dates['ini'][] = date_format($date, "Y-m-d 00:00:00");
        $dates['end'][] = date_format($date, "Y-m-t 23:59:59");
        $dates['mLabel'][] = getMonthLabel((int)date_format($date, "m"));
    }

    return $dates;
}


function secToTime(int $secs): array
{
    $time = array("seconds" => 0, "minutes" => 0, "hours" => 0, "verbose" => "");
    $time['seconds'] = $secs % 60;
    $secs = ($secs - $time['seconds']) / 60;
    $time['minutes'] = $secs % 60;
    $time['hours'] = ($secs - $time['minutes']) / 60;
    
    $time['verbose'] = $time['hours'] . "h " . $time['minutes'] . "m " . $time['seconds'] . "s";

    return $time;
}

/**
 * Trunca (formata) a exibição do tempo de acordo com o número de elementos definidos em $nSets
 * @param string $time
 * @param integer $nSets
 * 
 * @return string
 */
function truncateTime ($time, $nSets): string
{
    $newTime = trim($time);
    $nSets = ($nSets == 0 ? 1 : $nSets);
    $sets = explode(" ", $time);
    if ($nSets < count($sets)) {
        $newTime = "";
        for ($i = 0; $i < $nSets; $i++) {
            $newTime .= $sets[$i] ." ";
        }
        $newTime = trim($newTime) . "..";
    }
    return $newTime;
}


/**
 * Insere um registro em ocorrencias_log com o estado atual do chamado caso esse registro não exista
 * @param $conn: conexão
 * @param $numero: número do chamado
 * @param $tipo_edicao: código do tipo de edição - (0: abertura, 1: edição, ...)
 * @param $auto_record
 */
function firstLog($conn, $numero, $tipo_edicao='NULL', $auto_record = '') {
    
    /* $tipo_edicao='NULL' */
    include ("../../includes/queries/queries.php");
    
    //Checando se já existe um registro para o chamado
    $sql_log_base = "SELECT * FROM ocorrencias_log WHERE log_numero = '".$numero."' ";
    $qry = $conn->query($sql_log_base);
    $existe_log = $qry->rowCount();

    if (!$existe_log){//AINDA NAO EXISTE REGISTRO - NESSE CASO ADICIONO UM REGISTRO COMPLETO COM O ESTADO ATUAL DO CHAMADO
    
        $qryfull = $QRY["ocorrencias_full_ini"]." WHERE o.numero = " . $numero;
        $qFull = $conn->query($qryfull);
        $rowfull = $qFull->fetch(PDO::FETCH_OBJ);
        
        $base_descricao = $rowfull->descricao;
        $base_departamento = $rowfull->setor_cod;
        $base_area = $rowfull->area_cod;
        $base_prioridade = $rowfull->oco_prior;
        $base_problema = $rowfull->prob_cod;
        $base_unidade = $rowfull->unidade_cod;
        $base_etiqueta = $rowfull->etiqueta;
        $base_contato = $rowfull->contato;
        $base_contato_email = $rowfull->contato_email;
        $base_telefone = $rowfull->telefone;
        $base_operador = $rowfull->operador_cod;
        $base_data_agendamento = $rowfull->oco_scheduled_to;
        $base_status = $rowfull->status_cod;
        
        $val = array();
        $val['log_numero'] = $rowfull->numero;
        
        if ($auto_record == ''){
            $val['log_quem'] = $_SESSION['s_uid'];
        } else
            $val['log_quem'] = $base_operador;            
        
        // $val['log_data'] = date("Y-m-d H:i:s");            
        $val['log_data'] = $rowfull->oco_real_open_date;            
        $val['log_prioridade'] = ($rowfull->oco_prior == "" || $rowfull->oco_prior == "-1" )?'NULL':"'$base_prioridade'";  
        $val['log_descricao'] = $rowfull->descricao == ""?'NULL':"'$base_descricao'";  
        $val['log_area'] = ($rowfull->area_cod == "" || $rowfull->area_cod =="-1")?'NULL':"'$base_area'";  
        $val['log_problema'] = ($rowfull->prob_cod == "" || $rowfull->prob_cod =="-1")?'NULL':"'$base_problema'";  
        $val['log_unidade'] = ($rowfull->unidade_cod == "" || $rowfull->unidade_cod =="-1" || $rowfull->unidade_cod =="0")?'NULL':"'$base_unidade'";  
        $val['log_etiqueta'] = ($rowfull->etiqueta == "" || $rowfull->etiqueta =="-1" || $rowfull->etiqueta =="0")?'NULL':"'$base_etiqueta'";  
        $val['log_contato'] = ($rowfull->contato == "")?'NULL':"'$base_contato'";  
        $val['log_contato_email'] = ($rowfull->contato_email == "")?'NULL':"'$base_contato_email'";  
        $val['log_telefone'] = ($rowfull->telefone == "")?'NULL':"'$base_telefone'";  
        $val['log_departamento'] = ($rowfull->setor_cod == "" || $rowfull->setor_cod =="-1")?'NULL':"'$base_departamento'";  
        $val['log_responsavel'] = ($rowfull->operador_cod == "" || $rowfull->operador_cod =="-1")?'NULL':"'$base_operador'";  
        $val['log_data_agendamento'] = ($rowfull->oco_scheduled_to == "")?'NULL':"'$base_data_agendamento'";  
        $val['log_status'] = ($rowfull->status_cod == "" || $rowfull->status_cod =="-1")?'NULL':"'$base_status'";  
        $val['log_tipo_edicao'] = $tipo_edicao;
        
    
        //GRAVA O REGISTRO DE LOG DO ESTADO ANTERIOR A EDICAO
        $sql_base = "INSERT INTO `ocorrencias_log` ".
            "\n\t(`log_numero`, `log_quem`, `log_data`, `log_descricao`, `log_prioridade`, ".
            "\n\t`log_area`, `log_problema`, `log_unidade`, `log_etiqueta`, ".
            "\n\t`log_contato`, `log_contato_email`, `log_telefone`, `log_departamento`, `log_responsavel`, `log_data_agendamento`, ".
            "\n\t`log_status`, ".
            "\n\t`log_tipo_edicao`) ".
            "\nVALUES ".
            "\n\t('".$val['log_numero']."', '".$val['log_quem']."', '".$val['log_data']."', ".$val['log_descricao'].", ".$val['log_prioridade'].", ".
            "\n\t".$val['log_area'].", ".$val['log_problema'].", ".$val['log_unidade'].", ".$val['log_etiqueta'].", ".
            "\n\t".$val['log_contato'].", ".$val['log_contato_email'].", ".$val['log_telefone'].", ".$val['log_departamento'].", ".$val['log_responsavel'].", ".$val['log_data_agendamento'].", ".
            "\n\t".$val['log_status'].", ".
            "\n\t".$val['log_tipo_edicao']." ".
            "\n\t )";
        
        try {
            $conn->exec($sql_base);
            return true;
        }
        catch (Exception $e) {
            return false;
        }
    }
    return false;
}

/**
 * Grava o registro de modificações do chamado na tabela ocorrencias_log
 * @param $conn: conexão
 * @param int $ticket: número do chamado
 * @param array $beforePost: array de informações do chamado antes de sofrer modificações
 * @param array $afterPost: array das informações postadas para modificar o chamado
 * @param int $operationType: código do tipo de operação - 0:abertura | 1:edição... ver o restante
 * @return bool: true se conseguir realizar a inserção e false em caso de falha
 */
function recordLog($conn, int $ticket, array $beforePost, array $afterPost, int $operationType): bool
{
    $logPrioridade = (array_key_exists("prioridade", $afterPost) ? $afterPost['prioridade'] : "dontCheck");
    $logArea = (array_key_exists("area", $afterPost) ? $afterPost['area'] : "dontCheck");
    $logProblema = (array_key_exists("problema", $afterPost) ? $afterPost['problema'] : "dontCheck");
    $logUnidade = (array_key_exists("unidade", $afterPost) ? $afterPost['unidade'] : "dontCheck");
    $logEtiqueta = (array_key_exists("etiqueta", $afterPost) ? $afterPost['etiqueta'] : "dontCheck");
    $logContato = (array_key_exists("contato", $afterPost) ? $afterPost['contato'] : "dontCheck");
    $logContatoEmail = (array_key_exists("contato_email", $afterPost) ? $afterPost['contato_email'] : "dontCheck");
    $logTelefone = (array_key_exists("telefone", $afterPost) ? $afterPost['telefone'] : "dontCheck");
    $logDepartamento = (array_key_exists("departamento", $afterPost) ? $afterPost['departamento'] : "dontCheck");
    $logOperador = (array_key_exists("operador", $afterPost) ? $afterPost['operador'] : "dontCheck");
    $logStatus = (array_key_exists("status", $afterPost) ? $afterPost['status'] : "dontCheck");

    $logAgendadoPara = (array_key_exists("agendadoPara", $afterPost) ? $afterPost['agendadoPara'] : "dontCheck");

    $val = array();
    $val['log_numero'] = $ticket;
    $val['log_quem'] = $_SESSION['s_uid'];            
    $val['log_data'] = date("Y-m-d H:i:s");            

    if ($logPrioridade == "dontCheck") $val['log_prioridade'] = 'NULL'; else
        $val['log_prioridade'] = (($beforePost['oco_prior'] == $logPrioridade) || ((empty($beforePost['oco_prior']) || $beforePost['oco_prior']=="-1" || $beforePost['oco_prior']==NULL)  && ($logPrioridade == "" || $logPrioridade == "-1" || $logPrioridade == NULL)))?'NULL': "'$logPrioridade'"; 
    
    if ($logArea == "dontCheck") $val['log_area'] = 'NULL'; else
        $val['log_area'] = ($beforePost['area_cod'] == $logArea)?'NULL':"'$logArea'";
    
    if ($logProblema == "dontCheck") $val['log_problema'] = 'NULL'; else
        $val['log_problema'] = ($beforePost['prob_cod'] == $logProblema)?'NULL':"'$logProblema'";
    
    if ($logUnidade == "dontCheck") $val['log_unidade'] = 'NULL'; else
        $val['log_unidade'] = (($beforePost['unidade_cod'] == $logUnidade) || ((empty($beforePost['unidade_cod']) || $beforePost['unidade_cod']=="-1" || $beforePost['unidade_cod']==NULL)  && ($logUnidade == "" || $logUnidade == "-1" || $logUnidade == NULL)))?'NULL':"'$logUnidade'";  

    if ($logEtiqueta == "dontCheck") $val['log_etiqueta'] = 'NULL'; else
        $val['log_etiqueta'] = ($beforePost['etiqueta'] == $logEtiqueta)?'NULL':"'".noHtml($logEtiqueta)."'";

    if ($logContato == "dontCheck") $val['log_contato'] = 'NULL'; else
        $val['log_contato'] = ($beforePost['contato'] == $logContato)?'NULL':"'".noHtml($logContato)."'";
    
    if ($logContatoEmail == "dontCheck") $val['log_contato_email'] = 'NULL'; else
        $val['log_contato_email'] = ($beforePost['contato_email'] == $logContatoEmail)?'NULL':"'".noHtml($logContatoEmail)."'";

    if ($logTelefone == "dontCheck") $val['log_telefone'] = 'NULL'; else
        $val['log_telefone'] = ($beforePost['telefone'] == $logTelefone)?'NULL':"'$logTelefone'";

    if ($logDepartamento == "dontCheck") $val['log_departamento'] = 'NULL'; else    
        $val['log_departamento'] = (($beforePost['setor_cod'] == $logDepartamento) || ((empty($beforePost['setor_cod']) || $beforePost['setor_cod']=="-1" || $beforePost['setor_cod']==NULL)  && ($logDepartamento == "" || $logDepartamento == "-1" || $logDepartamento == NULL)))?'NULL':"'$logDepartamento'"; 

    if ($logOperador == "dontCheck") $val['log_responsavel'] = 'NULL'; else
        $val['log_responsavel'] = ($beforePost['operador_cod'] == $logOperador)?'NULL':"'$logOperador'";

    if ($logStatus == "dontCheck") $val['log_status'] = 'NULL'; else
        $val['log_status'] = ($beforePost['status_cod'] == $logStatus)?'NULL':"'$logStatus'";

    if ($logAgendadoPara == "dontCheck") $val['log_data_agendamento'] = 'NULL'; else
        $val['log_data_agendamento'] = ($beforePost['oco_scheduled_to'] == $logAgendadoPara || $logAgendadoPara == "")?'NULL':"'$logAgendadoPara'";

    $val['log_tipo_edicao'] = $operationType; //Edição     


    //GRAVA O REGISTRO DE LOG DA ALTERACAO REALIZADA
    $sqlLog = "INSERT INTO `ocorrencias_log` 
    (`log_numero`, `log_quem`, `log_data`, `log_prioridade`, 
    `log_area`, `log_problema`, `log_unidade`, `log_etiqueta`, `log_departamento`, 
    `log_contato`, `log_contato_email`, `log_telefone`, `log_responsavel`, 
    `log_data_agendamento`, `log_status`, 
    `log_tipo_edicao`) 
    VALUES 
    ('".$val['log_numero']."', '".$val['log_quem']."', '".$val['log_data']."', ".$val['log_prioridade'].", 
    ".$val['log_area'].", ".$val['log_problema'].", ".$val['log_unidade'].", ".$val['log_etiqueta'].", 
    ".$val['log_departamento'].",
    ".$val['log_contato'].", ".$val['log_contato_email'].", ".$val['log_telefone'].", ".$val['log_responsavel'].", ". $val['log_data_agendamento'].", 
    ".$val['log_status'].", ".$val['log_tipo_edicao'].")";

    try {
        $conn->exec($sqlLog);
        return true;
    }
    catch (Exception $e) {
        echo $e->getMessage() . "<br/>" . $sqlLog . "<br/>";
        return false;
    }
}


function detectUTF8($string)
{
        return preg_match('%(?:
        [\xC2-\xDF][\x80-\xBF]        # non-overlong 2-byte
        |\xE0[\xA0-\xBF][\x80-\xBF]               # excluding overlongs
        |[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}      # straight 3-byte
        |\xED[\x80-\x9F][\x80-\xBF]               # excluding surrogates
        |\xF0[\x90-\xBF][\x80-\xBF]{2}    # planes 1-3
        |[\xF1-\xF3][\x80-\xBF]{3}                  # planes 4-15
        |\xF4[\x80-\x8F][\x80-\xBF]{2}    # plane 16
        )+%xs', $string);
}

/**
 * DETECTS IF THE GIVEN STRING IS IN UTF8 AND CONVERTS TO ISO-88591
 * @author Flavio Ribeiro
 * char
 *
 * @param string $string
 * 
 * @return null|string
 */
function char(?string $string): ?string
{
    if (isset($string)){
        if (detectUTF8($string))
        return utf8_decode($string);
        return $string;
    } 
    return null;
}
