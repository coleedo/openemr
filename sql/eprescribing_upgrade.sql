--
--  Comment Meta Language for sql upgrades:
--
--  Each section within an upgrade sql file is enveloped with an #If*/#EndIf block.  At first glance, these appear to be standard mysql
--  comments meant to be cryptic hints to -other developers about the sql goodness contained therein.  However, were you to rely on such basic premises,
--  you would find yourself grossly decieved.  Indeed, without the knowledge that these comments are, in fact a sneakily embedded meta langauge derived
--  for a purpose none-other than to aid in the protection of the database during upgrades,  you would no doubt be subject to much ridicule and public
--  beratement at the hands of the very developers who envisioned such a crafty use of comments. -jwallace
--
--  While these lines are as enigmatic as they are functional, there is a method to the madness.  Let's take a moment to briefly go over proper comment meta language use.
--
--  The #If* sections have the behavior of functions and come complete with arguments supplied command-line style
--
--  Your Comment meta language lines cannot contain any other comment styles such as the nefarious double dashes "--" lest your lines be skipped and
--  the blocks automatcially executed with out regard to the existing database state.
--
--  Comment Meta Language Constructs:
--
--  #IfNotTable
--    argument: table_name
--    behavior: if the table_name does not exist,  the block will be executed

--  #IfTable
--    argument: table_name
--    behavior: if the table_name does exist, the block will be executed

--  #IfMissingColumn
--    arguments: table_name colname
--    behavior:  if the colname in the table_name table does not exist,  the block will be executed

--  #IfNotColumnType
--    arguments: table_name colname value
--    behavior:  If the table table_name does not have a column colname with a data type equal to value, then the block will be executed

--  #IfNotRow
--    arguments: table_name colname value
--    behavior:  If the table table_name does not have a row where colname = value, the block will be executed.

--  #IfNotRow2D
--    arguments: table_name colname value colname2 value2
--    behavior:  If the table table_name does not have a row where colname = value AND colname2 = value2, the block will be executed.

--  #IfNotRow2Dx2
--    desc:      This is a very specialized function to allow adding items to the list_options table to avoid both redundant option_id and title in each element.
--    arguments: table_name colname value colname2 value2 colname3 value3
--    behavior:  The block will be executed if both statements below are true:
--               1) The table table_name does not have a row where colname = value AND colname2 = value2.
--               2) The table table_name does not have a row where colname = value AND colname3 = value3.

--  #EndIf
--    all blocks are terminated with and #EndIf statement.


#IfMissingColumn lists reaction 
ALTER TABLE `lists`
  ADD `reaction` VARCHAR(255) NOT NULL DEFAULT '';
#EndIf

#IfMissingColumn lists ingredient  
ALTER TABLE `lists`
  ADD `ingredient` VARCHAR(100) NOT NULL DEFAULT '';
#EndIf

#IfMissingColumn lists drug_id  
ALTER TABLE `lists`
  ADD `drug_id` VARCHAR(20) NOT NULL DEFAULT '';
#EndIf

#IfNotRow2Dx2 list_options list_id lists option_id message_type title Message Types
INSERT INTO list_options (list_id,option_id,title,seq,is_default,option_value) values
('lists','message_type','Message Types',29,0,0);
#EndIf

#IfNotRow2Dx2 list_options list_id message_types option_id A title Administrative
INSERT INTO list_options (list_id,option_id,title,seq,is_default,option_value) values
('message_type','A','Administrative',1,0,0);
#EndIf

#IfNotRow2Dx2 list_options list_id message_types option_id B title Billing
INSERT INTO list_options (list_id,option_id,title,seq,is_default,option_value) values
('message_type','B','Billing, Inqueries',2,0,0);
#EndIf

#IfNotRow2Dx2 list_options list_id message_types option_id C title Clinical, Observation
INSERT INTO list_options (list_id,option_id,title,seq,is_default,option_value) values
('message_type','C','Clinical, Observation',3,0,0);
#EndIf

#IfNotRow2Dx2 list_options list_id message_types option_id E title Escript
INSERT INTO list_options (list_id,option_id,title,seq,is_default,option_value) values
('message_type','E','Escript',4,0,0);
#EndIf

#IfNotRow2Dx2 list_options list_id message_types option_id F title Referral
INSERT INTO list_options (list_id,option_id,title,seq,is_default,option_value) values
('message_type','F','Referral',5,0,0);
#EndIf

#IfNotRow2Dx2 list_options list_id message_types option_id I title Issues, Problems
INSERT INTO list_options (list_id,option_id,title,seq,is_default,option_value) values
('message_type','I','Issues, Problems',6,0,0);
#EndIf

#IfNotRow2Dx2 list_options list_id message_types option_id N title Renewal
INSERT INTO list_options (list_id,option_id,title,seq,is_default,option_value) values
('message_type','N','Renewal',7,0,0);
#EndIf

#IfNotRow2Dx2 list_options list_id message_types option_id O title Physician Notes
INSERT INTO list_options (list_id,option_id,title,seq,is_default,option_value) values
('message_type','O','Physician Notes',8,0,0);
#EndIf

#IfNotRow2Dx2 list_options list_id message_types option_id P title Patient Notes
INSERT INTO list_options (list_id,option_id,title,seq,is_default,option_value) values
('message_type','P','Patient Notes',9,0,0);
#EndIf

#IfNotRow2Dx2 list_options list_id message_types option_id R title Lab, Test Results
INSERT INTO list_options (list_id,option_id,title,seq,is_default,option_value) values
('message_type','R','Lab, Test Results',10,0,0);
#EndIf

#IfNotRow2Dx2 list_options list_id message_types option_id V title Office Appnt, Web Visit
INSERT INTO list_options (list_id,option_id,title,seq,is_default,option_value) values
('message_type','V','Office Appnt, Web Visit',11,0,0);
#EndIf

#IfNotRow2Dx2 list_options list_id message_types option_id W title Website, User
INSERT INTO list_options (list_id,option_id,title,seq,is_default,option_value) values
('message_type','W','Website, User',12,0,0);
#EndIf

#IfNotRow2Dx2 list_options list_id message_types option_id X title Prescription, Medication
INSERT INTO list_options (list_id,option_id,title,seq,is_default,option_value) values
('message_type','X','Prescription, Medication',13,0,0);
#EndIf

#IfNotTable
CREATE TABLE e_inbox (
  `e_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `e_extid` bigint(20) DEFAULT NULL,
  `e_mtid` tinyint(4) DEFAULT NULL,
  `e_type` varchar(5) DEFAULT NULL,
  `e_date` datetime DEFAULT NULL,
  `e_recipientid` bigint(20) DEFAULT NULL,
  `e_senderid` bigint(20) DEFAULT NULL,
  `e_source` varchar(50) NOT NULL,
  `e_destination` varchar(50) DEFAULT NULL,
  `e_facility` varchar(50) DEFAULT NULL,
  `e_env_code` varchar(50) DEFAULT NULL,
  `e_method` varchar(4) DEFAULT NULL,
  `e_status` varchar(1) DEFAULT NULL,
  `e_pid` bigint(20) DEFAULT NULL,
  `e_mrn` varchar(16) DEFAULT NULL,
  `e_pv1` varchar(10) DEFAULT NULL,
  `e_attending_id` varchar(50) DEFAULT NULL,
  `e_referring_id` varchar(50) DEFAULT NULL,
  `e_inbox` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`e_id`)
) ENGINE=MyISAM;
#EndIf

#IfNotTable
CREATE TABLE e_outbox (
  `e_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `e_mtid` tinyint(4) DEFAULT NULL,
  `e_type` varchar(5) DEFAULT NULL,
  `e_date` datetime DEFAULT NULL,
  `e_recipient` bigint(20) DEFAULT NULL,
  `e_sender` bigint(20) DEFAULT NULL,
  `e_source` varchar(50) NOT NULL,
  `e_destination` varchar(50) DEFAULT NULL,
  `e_facility` varchar(50) DEFAULT NULL,
  `e_env_code` varchar(50) DEFAULT NULL,
  `e_method` varchar(4) DEFAULT NULL,
  `e_status` varchar(1) DEFAULT NULL,
  `e_pid` bigint(20) DEFAULT NULL,
  `e_mrn` varchar(16) DEFAULT NULL,
  `e_provider_id` bigint(20) DEFAULT NULL,
  `e_attending_id` varchar(50) DEFAULT NULL,
  `e_referring_id` varchar(50) DEFAULT NULL,
  `e_inbox` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`e_id`)
) ENGINE=MyISAM;
#EndIf

#IfNotTable
CREATE TABLE e_types (
  `et_id` tinyint(4) NOT NULL AUTO_INCREMENT,
  `et_type` varchar(20) NOT NULL,
  `et_map` varchar(30) DEFAULT NULL,
  `et_source` varchar(20) DEFAULT NULL,
  `et_code_type` varchar(25) DEFAULT NULL,
  `et_code` varchar(25) DEFAULT NULL,
  `et_name` varchar(50) DEFAULT NULL,
  `et_description` varchar(100) DEFAULT NULL,
  `et_status` tinyint(4) NOT NULL,
  PRIMARY KEY (`et_id`)
) ENGINE=MyISAM;
#EndIf

#IfNotTable
CREATE TABLE e_notes (
  `en_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `en_table` varchar(50) DEFAULT NULL,
  `en_tid` bigint(20) DEFAULT NULL,
  `en_seq` smallint(10) DEFAULT NULL,
  `en_date` date DEFAULT NULL,
  `en_type` varchar(20) DEFAULT NULL,
  `en_status` varchar(50) DEFAULT NULL,
  `en_body` text,
  PRIMARY KEY (`en_id`)
) ENGINE=MyISAM;
#EndIf

#IfNotTable
CREATE TABLE e_docs (
  `ed_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `ed_table` varchar(50) DEFAULT NULL,
  `ed_tid` bigint(20) NOT NULL,
  `ed_seq` smallint(10) NOT NULL,
  `ed_date` date DEFAULT NULL,
  `ed_name` varchar(50) DEFAULT NULL,
  `ed_type` varchar(5) DEFAULT NULL,
  `ed_status` varchar(50) DEFAULT NULL,
  `ed_file_path` varchar(150) DEFAULT NULL,
  PRIMARY KEY (`ed_id`)
) ENGINE=MyISAM;
#EndIf

#IfNotTable
CREATE TABLE e_units (
  `eu_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `eu_code` varchar(3) NOT NULL,
  `eu_description` varchar(30) NOT NULL,
  PRIMARY KEY (`eu_id`)
) ENGINE=MyISAM;
#EndIf

#IfNotRow2D e_types et_source relayhealth e_type WV
INSERT INTO e_types (et_type,et_map,et_source,et_code,et_code_type,et_name,et_description,et_status) VALUES
('WV','V','relayhealth','2','TXA','WV','webVisit','1');
#EndIf

#IfNotRow2D e_types et_source relayhealth e_type NT
INSERT INTO e_types (et_type,et_map,et_source,et_code,et_code_type,et_name,et_description,et_status) VALUES
('NT','A','relayhealth','2','TXA','NT','Note to Office','1');
#EndIf

#IfNotRow2D e_types et_source relayhealth e_type MD
INSERT INTO e_types (et_type,et_map,et_source,et_code,et_code_type,et_name,et_description,et_status) VALUES
('MD','O','relayhealth','2','TXA','MD','Note to Doctor','1');
#EndIf

#IfNotRow2D e_types et_source relayhealth e_type LQ
INSERT INTO e_types (et_type,et_map,et_source,et_code,et_code_type,et_name,et_description,et_status) VALUES
('LQ','R','relayhealth','2','TXA','LQ','Lab/Test Result Request','1');
#EndIf

#IfNotRow2D e_types et_source relayhealth e_type RN
INSERT INTO e_types (et_type,et_map,et_source,et_code,et_code_type,et_name,et_description,et_status) VALUES
('RN','N','relayhealth','2','TXA','RN','Rx Renewal Request','1');
#EndIf

#IfNotRow2D e_types et_source relayhealth e_type RF
INSERT INTO e_types (et_type,et_map,et_source,et_code,et_code_type,et_name,et_description,et_status) VALUES
('RF','F','relayhealth','2','TXA','RF','Referral Request','1');
#EndIf

#IfNotRow2D e_types et_source relayhealth e_type BL
INSERT INTO e_types (et_type,et_map,et_source,et_code,et_code_type,et_name,et_description,et_status) VALUES
('BL','B','relayhealth','2','TXA','BL','Billing Question','1');
#EndIf

#IfNotRow2D e_types et_source relayhealth e_type RQ
INSERT INTO e_types (et_type,et_map,et_source,et_code,et_code_type,et_name,et_description,et_status) VALUES
('RQ','P','relayhealth','2','TXA','RQ','New Patient','1');
#EndIf

#IfNotRow2D e_types et_source relayhealth e_type AP
INSERT INTO e_types (et_type,et_map,et_source,et_code,et_code_type,et_name,et_description,et_status) VALUES
('AP','V','relayhealth','2','TXA','AP','Appointment Request','1');
#EndIf

#IfNotRow2D e_types et_source relayhealth e_type RX
INSERT INTO e_types (et_type,et_map,et_source,et_code,et_code_type,et_name,et_description,et_status) VALUES
('RX','X','relayhealth','2','TXA','RX','New Medication','1');
#EndIf

#IfNotRow2D e_types et_source relayhealth e_type RE
INSERT INTO e_types (et_type,et_map,et_source,et_code,et_code_type,et_name,et_description,et_status) VALUES
('RE','F','relayhealth','2','TXA','RE','Referral from Physician','1');
#EndIf

#IfNotRow2D e_types et_source relayhealth e_type RD
INSERT INTO e_types (et_type,et_map,et_source,et_code,et_code_type,et_name,et_description,et_status) VALUES
('RD','N','relayhealth','2','TXA','RD','Renewal Denied','1');
#EndIf

#IfNotRow2D e_types et_source relayhealth e_type ER
INSERT INTO e_types (et_type,et_map,et_source,et_code,et_code_type,et_name,et_description,et_status) VALUES
('ER','N','relayhealth','2','TXA','ER','eRenewal Request','1');
#EndIf

#IfNotTable ndc_code_list
CREATE TABLE ndc_code_list (
`list_id`                  int(7)       NOT NULL,
`ndc_number`               int(11)      NOT NULL,
`ndc_code`                 char(14)     NULL,
KEY (list_id),
KEY (ndc_number),
KEY (ndc_code)
) ENGINE=MyISAM;
#EndIf

#IfNotTable
CREATE TABLE ndc_drug_list (
`list_id`                  int(7)        NOT NULL,
`label_code`               char(6)       NOT NULL,
`product_code`             char(4)       NOT NULL,
`strength`                 char(10)      NULL,
`unit`                     char(10)      NULL,
`rx_otc`                   char(1)       NOT NULL,
`name`                     char(100)     NOT NULL,
PRIMARY KEY (list_id)
) ENGINE=MyISAM;
#EndIf

#IfNotTable
CREATE TABLE ndc_packages (
`pkg_id`                   int(8)        NOT NULL auto_increment,
`list_id`                  int(8)        NOT NULL,
`code`                     char(2)       NULL,
`size`                     char(25)      NOT NULL,
`type`                     char(25)      NOT NULL,
PRIMARY KEY (pkg_id),
KEY (list_id)
) ENGINE=MyISAM;
#EndIf

#IfNotTable
CREATE TABLE ndc_forms (
`form_id`                  int(8)        NOT NULL auto_increment,
`list_id`                  int(8)        NOT NULL,
`strength`                 char(10)      NULL,
`unit`                     char(5)       NULL,
`ingredient_name`          char(100)     NOT NULL,
PRIMARY KEY (form_id),
KEY (list_id)
) ENGINE=MyISAM;
#EndIf

#IfNotTable
CREATE TABLE ndc_new_app (
`new_id`                   int(8)        NOT NULL auto_increment,
`list_id`                  int(8)        NOT NULL,
`app_no`                   char(6)       NULL,
`prod_no`                  char(3)       NULL,
PRIMARY KEY (new_id),
KEY (list_id)
) ENGINE=MyISAM;
#EndIf

#IfNotTable
CREATE TABLE ndc_firms (
`firm_id`                  int(8)        NOT NULL auto_increment,
`label_code`               char(6)       NOT NULL,
`name`                     char(65)      NOT NULL,
`addr_head`                char(40)      NULL,
`address`                  char(40)      NULL,
`po_box`                   char(9)       NULL,
`addr_foreign`             char(40)      NULL,
`city`                     char(30)      NULL,
`state`                    char(2)       NULL,
`zip`                      char(9)       NULL,
`province`                 char(30)      NULL,
`country`                  char(40)      NOT NULL,
PRIMARY KEY (firm_id),
KEY (label_code)
) ENGINE=MyISAM;
#EndIf

#IfNotTable
CREATE TABLE ndc_list_dosage (
`dosage_id`                  int(8)      NOT NULL auto_increment,
`list_id`                    int(8)      NOT NULL,
`code`                       char(3)     NOT NULL,
`name`                       char(240)   NULL,
PRIMARY KEY (dosage_id),
KEY (list_id)
) ENGINE=MyISAM;
#EndIf

#IfNotTable
CREATE TABLE ndc_list_route (
`route_id`                  int(8)       NOT NULL auto_increment,
`list_id`                   int(8)       NOT NULL,
`code`                      char(3)      NOT NULL,
`name`                      char(240)    NULL,
PRIMARY KEY (route_id),
KEY (list_id)
) ENGINE=MyISAM;
#EndIf

#IfNotTable
CREATE TABLE ndc_dosage (
`dosage_code`               char(3)      NOT NULL,
`translation`               char(100)    NULL,
PRIMARY KEY (dosage_code)
) ENGINE=MyISAM;
#EndIf

#IfNotTable
CREATE TABLE ndc_route (
`route_code`                char(3)      NOT NULL,
`translation`               char(100)    NULL,
PRIMARY KEY (route_code)
) ENGINE=MyISAM;
#EndIf

#IfNotTable
CREATE TABLE ndc_unit (
`unit_code`                 char(15)     NOT NULL,
`translation`               char(100)    NULL,
PRIMARY KEY (unit_code)
) ENGINE=MyISAM;
#EndIf

#IfNotTable
CREATE TABLE ndc_schedule (
`list_id`                   int(8)       NOT NULL,
`code`                      int(1)       NOT NULL,
PRIMARY KEY (list_id)
) ENGINE=MyISAM;
#EndIf

