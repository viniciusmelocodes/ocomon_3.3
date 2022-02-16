

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Estrutura para tabela `asset_statements`
--

CREATE TABLE `asset_statements` (
  `id` int(11) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `header` text,
  `title` text,
  `p1_bfr_list` text,
  `p2_bfr_list` text,
  `p3_bfr_list` text,
  `p1_aft_list` text,
  `p2_aft_list` text,
  `p3_aft_list` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Textos para os termos de responsabilidade';

--
-- Despejando dados para a tabela `asset_statements`
--

INSERT INTO `asset_statements` (`id`, `slug`, `name`, `header`, `title`, `p1_bfr_list`, `p2_bfr_list`, `p3_bfr_list`, `p1_aft_list`, `p2_aft_list`, `p3_aft_list`) VALUES
(1, 'termo-compromisso', 'Termo de Compromisso', 'CENTRO DE INFORMÁTICA - SIGLA / SUPORTE AO USUÁRIO - HELPDESK', 'Termo de Compromisso para Equipamento', 'Por esse termo acuso o recebimento do(s) equipamento(s) abaixo especificado(s), comprometendo-me a mantê-lo(s) sob a minha guarda e responsabilidade, dele(s) fazendo uso adequado, de acordo com a resolução xxx/ano que define políticas, normas e procedimentos que disciplinam a utilização de equipamentos, recursos e serviços de informática da SUA_EMPRESA.', NULL, NULL, 'O suporte para qualquer problema que porventura vier a ocorrer na instalação ou operação do(s) equipamento(s), deverá ser solicitado à área de Suporte, através do telefone/ramal xxxx, pois somente através desde procedimento os chamados poderão ser registrados e atendidos.', 'Em conformidade com o preceituado no art. 1º da Resolução nº xxx/ano, é expressamente vedada a instalação de softwares sem a necessária licença de uso ou em desrespeito aos direitos autorais.', 'A SUA_EMPRESA, através do seu Departamento Responsável (XXXX), em virtude das suas disposições regimentais e regulamentadoras, adota sistema de controle de instalação de softwares em todos os seus equipamentos, impedindo a instalação destes sem prévia autorização do Departamento Competente.'),
(2, 'termo-transito', 'Formulário de Trânsito', 'CENTRO DE INFORMÁTICA - SIGLA / SUPORTE AO USUÁRIO - HELPDESK', 'Formulário de Trânsito de Equipamentos de Informática', 'Informo que o(s) equipamento(s) abaixo descriminado(s) está(ão) autorizado(s) pelo departamento responsável a serem transportados para fora da Unidade pelo portador citado.', NULL, NULL, 'A constatação de inconformidade dos dados aqui descritos no ato de verificação na portaria implica na não autorização de saída dos equipamentos, nesse caso o departamento responsável deve ser contactado.', NULL, NULL);


--
-- Índices de tabela `asset_statements`
--
ALTER TABLE `asset_statements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- AUTO_INCREMENT de tabela `asset_statements`
--
ALTER TABLE `asset_statements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;



ALTER TABLE `materiais` CHANGE `mat_cod` `mat_cod` INT(6) NOT NULL AUTO_INCREMENT; 
ALTER TABLE `materiais` CHANGE `mat_nome` `mat_nome` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL; 
ALTER TABLE `materiais` CHANGE `mat_caixa` `mat_caixa` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL; 
ALTER TABLE `materiais` CHANGE `mat_obs` `mat_obs` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL; 

ALTER TABLE `ocorrencias_log` CHANGE `log_data` `log_data` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP; 

CREATE TABLE `email_warranty_equipment` ( `id` INT NOT NULL AUTO_INCREMENT , `equipment_id` INT NOT NULL , `sent_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP , PRIMARY KEY (`id`), INDEX (`equipment_id`)) ENGINE = InnoDB COMMENT = 'Controle de envio de e-mails sobre vencimento garantia'; 


ALTER TABLE `config` ADD `conf_isolate_areas` INT(1) NOT NULL DEFAULT '0' COMMENT 'Visibilidade entre areas para consultas e relatorios' AFTER `conf_sla_tolerance`; 


ALTER TABLE `hw_alter` CHANGE `hwa_item` `hwa_item` INT(4) NULL; 

ALTER TABLE `mailconfig` ADD `mail_send` TINYINT(1) NOT NULL DEFAULT '1' AFTER `mail_from_name`; 

ALTER TABLE `modelos_itens` ADD `mdit_manufacturer` INT(6) NULL AFTER `mdit_cod`, ADD INDEX (`mdit_manufacturer`); 

ALTER TABLE `modelos_itens` CHANGE `mdit_fabricante` `mdit_fabricante` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL; 

ALTER TABLE `estoque` ADD `estoq_assist` INT(2) NULL DEFAULT NULL AFTER `estoq_partnumber`, ADD `estoq_warranty_type` INT(2) NULL DEFAULT NULL AFTER `estoq_assist`, ADD INDEX (`estoq_assist`), ADD INDEX (`estoq_warranty_type`); 


/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
