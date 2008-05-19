
--
-- Data Base: `centreon-2.x`
--

-- --------------------------------------------------------

--
-- Structure de la table `acl_groups`
--

CREATE TABLE IF NOT EXISTS `acl_groups` (
  `acl_group_id` int(11) NOT NULL auto_increment,
  `acl_group_name` varchar(255) default NULL,
  `acl_group_alias` varchar(255) default NULL,
  `acl_group_activate` enum('0','1','2') default NULL,
  PRIMARY KEY  (`acl_group_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `acl_group_contacts_relations`
--

CREATE TABLE IF NOT EXISTS `acl_group_contacts_relations` (
  `agcr_id` int(11) NOT NULL auto_increment,
  `contact_contact_id` int(11) default NULL,
  `acl_group_id` int(11) default NULL,
  PRIMARY KEY  (`agcr_id`),
  KEY `contact_contact_id` (`contact_contact_id`),
  KEY `acl_group_id` (`acl_group_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `acl_group_topology_relations`
--

CREATE TABLE IF NOT EXISTS `acl_group_topology_relations` (
  `agt_id` int(11) NOT NULL auto_increment,
  `acl_group_id` int(11) default NULL,
  `acl_topology_id` int(11) default NULL,
  PRIMARY KEY  (`agt_id`),
  KEY `acl_group_id` (`acl_group_id`),
  KEY `acl_topology_id` (`acl_topology_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `acl_resources`
--

CREATE TABLE IF NOT EXISTS `acl_resources` (
  `acl_res_id` int(11) NOT NULL auto_increment,
  `acl_res_name` varchar(255) default NULL,
  `acl_res_alias` varchar(255) default NULL,
  `acl_res_activate` enum('0','1','2') default NULL,
  `acl_res_comment` text,
  `acl_res_status` enum('0','1') default NULL,
  `changed` int(11) default NULL,
  PRIMARY KEY  (`acl_res_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `acl_resources_hg_relations`
--

CREATE TABLE IF NOT EXISTS `acl_resources_hg_relations` (
  `arhge_id` int(11) NOT NULL auto_increment,
  `hg_hg_id` int(11) default NULL,
  `acl_res_id` int(11) default NULL,
  PRIMARY KEY  (`arhge_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `acl_resources_hostex_relations`
--

CREATE TABLE IF NOT EXISTS `acl_resources_hostex_relations` (
  `arhe_id` int(11) NOT NULL auto_increment,
  `host_host_id` int(11) default NULL,
  `acl_res_id` int(11) default NULL,
  PRIMARY KEY  (`arhe_id`),
  KEY `host_host_id` (`host_host_id`),
  KEY `acl_res_id` (`acl_res_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `acl_resources_host_relations`
--

CREATE TABLE IF NOT EXISTS `acl_resources_host_relations` (
  `arhr_id` int(11) NOT NULL auto_increment,
  `host_host_id` int(11) default NULL,
  `acl_res_id` int(11) default NULL,
  PRIMARY KEY  (`arhr_id`),
  KEY `host_host_id` (`host_host_id`),
  KEY `acl_res_id` (`acl_res_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `acl_resources_sc_relations`
--

CREATE TABLE IF NOT EXISTS `acl_resources_sc_relations` (
  `arscr_id` int(11) NOT NULL auto_increment,
  `sc_id` int(11) default NULL,
  `acl_res_id` int(11) default NULL,
  PRIMARY KEY  (`arscr_id`),
  KEY `sc_id` (`sc_id`),
  KEY `acl_res_id` (`acl_res_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `acl_resources_service_relations`
--

CREATE TABLE IF NOT EXISTS `acl_resources_service_relations` (
  `arsr_id` int(11) NOT NULL auto_increment,
  `service_service_id` int(11) default NULL,
  `acl_group_id` int(11) default NULL,
  PRIMARY KEY  (`arsr_id`),
  KEY `service_service_id` (`service_service_id`),
  KEY `acl_group_id` (`acl_group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `acl_resources_sg_relations`
--

CREATE TABLE IF NOT EXISTS `acl_resources_sg_relations` (
  `asgr` int(11) NOT NULL auto_increment,
  `sg_id` int(11) default NULL,
  `acl_res_id` int(11) default NULL,
  PRIMARY KEY  (`asgr`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `acl_res_group_relations`
--

CREATE TABLE IF NOT EXISTS `acl_res_group_relations` (
  `argr_id` int(11) NOT NULL auto_increment,
  `acl_res_id` int(11) default NULL,
  `acl_group_id` int(11) default NULL,
  PRIMARY KEY  (`argr_id`),
  KEY `acl_res_id` (`acl_res_id`),
  KEY `acl_group_id` (`acl_group_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `acl_topology`
--

CREATE TABLE IF NOT EXISTS `acl_topology` (
  `acl_topo_id` int(11) NOT NULL auto_increment,
  `acl_topo_name` varchar(255) default NULL,
  `acl_topo_alias` varchar(255) default NULL,
  `acl_topo_activate` enum('0','1') default NULL,
  PRIMARY KEY  (`acl_topo_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `acl_topology_relations`
--

CREATE TABLE IF NOT EXISTS `acl_topology_relations` (
  `agt_id` int(11) NOT NULL auto_increment,
  `topology_topology_id` int(11) default NULL,
  `acl_topo_id` int(11) default NULL,
  PRIMARY KEY  (`agt_id`),
  KEY `topology_topology_id` (`topology_topology_id`),
  KEY `acl_topo_id` (`acl_topo_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `cfg_cgi`
--

CREATE TABLE IF NOT EXISTS `cfg_cgi` (
  `cgi_id` int(11) NOT NULL auto_increment,
  `cgi_name` varchar(255) default NULL,
  `main_config_file` varchar(255) default NULL,
  `physical_html_path` varchar(255) default NULL,
  `url_html_path` varchar(255) default NULL,
  `nagios_check_command` varchar(255) default NULL,
  `use_authentication` enum('0','1') default NULL,
  `default_user_name` varchar(255) default NULL,
  `authorized_for_system_information` text,
  `authorized_for_system_commands` text,
  `authorized_for_configuration_information` text,
  `authorized_for_all_hosts` text,
  `authorized_for_all_host_commands` text,
  `authorized_for_all_services` text,
  `authorized_for_all_service_commands` text,
  `statusmap_background_image` varchar(255) default NULL,
  `default_statusmap_layout` enum('0','1','2','3','4','5','6') default '2',
  `statuswrl_include` varchar(255) default NULL,
  `default_statuswrl_layout` enum('0','1','2','3','4') default '2',
  `refresh_rate` int(11) default NULL,
  `host_unreachable_sound` varchar(255) default NULL,
  `host_down_sound` varchar(255) default NULL,
  `service_critical_sound` varchar(255) default NULL,
  `service_warning_sound` varchar(255) default NULL,
  `service_unknown_sound` varchar(255) default NULL,
  `ping_syntax` text,
  `cgi_comment` text,
  `cgi_activate` enum('0','1') default NULL,
  PRIMARY KEY  (`cgi_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `cfg_nagios`
--

CREATE TABLE IF NOT EXISTS `cfg_nagios` (
  `nagios_id` int(11) NOT NULL auto_increment,
  `nagios_name` varchar(255) default NULL,
  `log_file` varchar(255) default NULL,
  `cfg_dir` varchar(255) default NULL,
  `object_cache_file` varchar(255) default NULL,
  `precached_object_file` varchar(255) default NULL,
  `temp_file` varchar(255) default NULL,
  `temp_path` varchar(255) default NULL,
  `status_file` varchar(255) default NULL,
  `check_result_path` varchar(255) default NULL,
  `max_check_result_file_age` varchar(255) default NULL,
  `p1_file` varchar(255) default NULL,
  `aggregate_status_updates` enum('0','1','2') default NULL,
  `status_update_interval` int(11) default NULL,
  `nagios_user` varchar(255) default NULL,
  `nagios_group` varchar(255) default NULL,
  `enable_notifications` enum('0','1','2') default NULL,
  `execute_service_checks` enum('0','1','2') default NULL,
  `accept_passive_service_checks` enum('0','1','2') default NULL,
  `execute_host_checks` enum('0','1','2') default NULL,
  `accept_passive_host_checks` enum('0','1','2') default NULL,
  `enable_event_handlers` enum('0','1','2') default NULL,
  `log_rotation_method` varchar(255) default NULL,
  `log_archive_path` varchar(255) default NULL,
  `check_external_commands` enum('0','1','2') default NULL,
  `command_check_interval` varchar(255) default NULL,
  `command_file` varchar(255) default NULL,
  `downtime_file` varchar(255) default NULL,
  `comment_file` varchar(255) default NULL,
  `lock_file` varchar(255) default NULL,
  `retain_state_information` enum('0','1','2') default NULL,
  `state_retention_file` varchar(255) default NULL,
  `retention_update_interval` int(11) default NULL,
  `use_retained_program_state` enum('0','1','2') default NULL,
  `use_retained_scheduling_info` enum('0','1','2') default NULL,
  `use_syslog` enum('0','1','2') default NULL,
  `log_notifications` enum('0','1','2') default NULL,
  `log_service_retries` enum('0','1','2') default NULL,
  `log_host_retries` enum('0','1','2') default NULL,
  `log_event_handlers` enum('0','1','2') default NULL,
  `log_initial_states` enum('0','1','2') default NULL,
  `log_external_commands` enum('0','1','2') default NULL,
  `log_passive_checks` enum('0','1','2') default NULL,
  `global_host_event_handler` int(11) default NULL,
  `global_service_event_handler` int(11) default NULL,
  `sleep_time` varchar(10) default NULL,
  `service_inter_check_delay_method` varchar(255) default NULL,
  `host_inter_check_delay_method` varchar(255) default NULL,
  `service_interleave_factor` varchar(255) default NULL,
  `max_concurrent_checks` int(11) default NULL,
  `max_service_check_spread` int(11) default NULL,
  `max_host_check_spread` int(11) default NULL,
  `service_reaper_frequency` int(11) default NULL,
  `interval_length` int(11) default NULL,
  `auto_reschedule_checks` enum('0','1','2') default NULL,
  `auto_rescheduling_interval` int(11) default NULL,
  `auto_rescheduling_window` int(11) default NULL,
  `use_agressive_host_checking` enum('0','1','2') default NULL,
  `enable_flap_detection` enum('0','1','2') default NULL,
  `low_service_flap_threshold` varchar(255) default NULL,
  `high_service_flap_threshold` varchar(255) default NULL,
  `low_host_flap_threshold` varchar(255) default NULL,
  `high_host_flap_threshold` varchar(255) default NULL,
  `soft_state_dependencies` enum('0','1','2') default NULL,
  `service_check_timeout` int(11) default NULL,
  `host_check_timeout` int(11) default NULL,
  `event_handler_timeout` int(11) default NULL,
  `notification_timeout` int(11) default NULL,
  `ocsp_timeout` int(11) default NULL,
  `ochp_timeout` int(11) default NULL,
  `perfdata_timeout` int(11) default NULL,
  `obsess_over_services` enum('0','1','2') default NULL,
  `ocsp_command` int(11) default NULL,
  `obsess_over_hosts` enum('0','1','2') default NULL,
  `ochp_command` int(11) default NULL,
  `process_performance_data` enum('0','1','2') default NULL,
  `host_perfdata_command` int(11) default NULL,
  `service_perfdata_command` int(11) default NULL,
  `host_perfdata_file` varchar(255) default NULL,
  `service_perfdata_file` varchar(255) default NULL,
  `host_perfdata_file_template` text,
  `service_perfdata_file_template` text,
  `host_perfdata_file_mode` enum('a','w','2') default NULL,
  `service_perfdata_file_mode` enum('a','w','2') default NULL,
  `host_perfdata_file_processing_interval` int(11) default NULL,
  `service_perfdata_file_processing_interval` int(11) default NULL,
  `host_perfdata_file_processing_command` int(11) default NULL,
  `service_perfdata_file_processing_command` int(11) default NULL,
  `check_for_orphaned_services` enum('0','1','2') default NULL,
  `check_service_freshness` enum('0','1','2') default NULL,
  `service_freshness_check_interval` int(11) default NULL,
  `freshness_check_interval` int(11) default NULL,
  `check_host_freshness` enum('0','1','2') default NULL,
  `host_freshness_check_interval` int(11) default NULL,
  `date_format` varchar(255) default NULL,
  `illegal_object_name_chars` varchar(255) default NULL,
  `illegal_macro_output_chars` varchar(255) default NULL,
  `use_regexp_matching` enum('0','1','2') default NULL,
  `use_true_regexp_matching` enum('0','1','2') default NULL,
  `admin_email` varchar(255) default NULL,
  `admin_pager` varchar(255) default NULL,
  `nagios_comment` text,
  `nagios_activate` enum('0','1') default NULL,
  `broker_module` varchar(255) default NULL,
  `event_broker_options` varchar(255) default NULL,
  `translate_passive_host_checks` enum('0','1') default NULL,
  `nagios_server_id` int(11) default NULL,
  `enable_predictive_host_dependency_checks` enum('0','1','2') default NULL,
  `enable_predictive_service_dependency_checks` enum('0','1','2') default NULL,
  `cached_host_check_horizon` int(11) default NULL,
  `cached_service_check_horizon` int(11) default NULL,
  `use_large_installation_tweaks` enum('0','1','2') default NULL,
  `free_child_process_memory` enum('0','1','2') default NULL,
  `child_processes_fork_twice` enum('0','1','2') default NULL,
  `enable_environment_macros` enum('0','1','2') default NULL,
  `additional_freshness_latency` int(11) default NULL,
  `enable_embedded_perl` enum('0','1','2') default NULL,
  `use_embedded_perl_implicitly` enum('0','1','2') default NULL,
  `debug_file` varchar(255) default NULL,
  `debug_level` int(11) default NULL,
  `debug_verbosity` enum('0','1','2') default NULL,
  `max_debug_file_size` int(11) default NULL,
  PRIMARY KEY  (`nagios_id`),
  KEY `cmd1_index` (`global_host_event_handler`),
  KEY `cmd2_index` (`global_service_event_handler`),
  KEY `cmd3_index` (`ocsp_command`),
  KEY `cmd4_index` (`ochp_command`),
  KEY `cmd5_index` (`host_perfdata_command`),
  KEY `cmd6_index` (`service_perfdata_command`),
  KEY `cmd7_index` (`host_perfdata_file_processing_command`),
  KEY `cmd8_index` (`service_perfdata_file_processing_command`),
  KEY `nagios_server_id` (`nagios_server_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `cfg_ndo2db`
--

CREATE TABLE IF NOT EXISTS `cfg_ndo2db` (
  `id` int(11) NOT NULL auto_increment,
  `description` varchar(255) collate latin1_general_ci default NULL,
  `ndo2db_user` varchar(255) collate latin1_general_ci default NULL,
  `ndo2db_group` varchar(255) collate latin1_general_ci default NULL,
  `local` enum('0','1') collate latin1_general_ci default '0',
  `ns_nagios_server` int(11) default NULL,
  `socket_type` varchar(255) collate latin1_general_ci default NULL,
  `socket_name` varchar(255) collate latin1_general_ci default NULL,
  `tcp_port` int(11) default NULL,
  `db_servertype` varchar(255) collate latin1_general_ci default NULL,
  `db_host` varchar(255) collate latin1_general_ci default NULL,
  `db_name` varchar(255) collate latin1_general_ci default NULL,
  `db_port` varchar(255) collate latin1_general_ci default NULL,
  `db_prefix` varchar(255) collate latin1_general_ci default NULL,
  `db_user` varchar(255) collate latin1_general_ci default NULL,
  `db_pass` varchar(255) collate latin1_general_ci default NULL,
  `max_timedevents_age` varchar(255) collate latin1_general_ci default NULL,
  `max_systemcommands_age` varchar(255) collate latin1_general_ci default NULL,
  `max_servicechecks_age` varchar(255) collate latin1_general_ci default NULL,
  `max_hostchecks_age` varchar(255) collate latin1_general_ci default NULL,
  `max_eventhandlers_age` varchar(255) collate latin1_general_ci default NULL,
  `activate` enum('0','1') collate latin1_general_ci default NULL,
  PRIMARY KEY  (`id`),
  KEY `ns_nagios_server` (`ns_nagios_server`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci COMMENT='configuration base for ndo daemon' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `cfg_ndomod`
--

CREATE TABLE IF NOT EXISTS `cfg_ndomod` (
  `id` int(11) NOT NULL auto_increment,
  `description` varchar(255) collate latin1_general_ci default NULL,
  `local` enum('0','1') collate latin1_general_ci default NULL,
  `ns_nagios_server` int(11) default NULL,
  `instance_name` varchar(255) collate latin1_general_ci default NULL,
  `output_type` varchar(255) collate latin1_general_ci default NULL,
  `output` varchar(255) collate latin1_general_ci default NULL,
  `tcp_port` varchar(255) collate latin1_general_ci default NULL,
  `output_buffer_items` int(11) default NULL,
  `buffer_file` varchar(255) collate latin1_general_ci default NULL,
  `file_rotation_interval` int(11) default NULL,
  `file_rotation_command` int(11) default NULL,
  `file_rotation_timeout` int(11) default NULL,
  `reconnect_interval` int(11) default NULL,
  `reconnect_warning_interval` int(11) default NULL,
  `data_processing_options` int(11) default NULL,
  `config_output_options` int(11) default NULL,
  `activate` enum('0','1') collate latin1_general_ci default NULL,
  PRIMARY KEY  (`id`),
  KEY `ns_nagios_server` (`ns_nagios_server`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci COMMENT='ndomog table config' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `cfg_resource`
--

CREATE TABLE IF NOT EXISTS `cfg_resource` (
  `resource_id` int(11) NOT NULL auto_increment,
  `resource_name` varchar(255) default NULL,
  `resource_line` varchar(255) default NULL,
  `resource_comment` varchar(255) default NULL,
  `resource_activate` enum('0','1') default NULL,
  PRIMARY KEY  (`resource_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

-- --------------------------------------------------------

--
-- Structure de la table `command`
--

CREATE TABLE IF NOT EXISTS `command` (
  `command_id` int(11) NOT NULL auto_increment,
  `command_name` varchar(200) default NULL,
  `command_line` text,
  `command_example` varchar(254) default NULL,
  `command_type` tinyint(4) default NULL,
  `graph_id` int(11) default NULL,
  `cmd_cat_id` int(11) default NULL,
  PRIMARY KEY  (`command_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `command_categories`
--

CREATE TABLE IF NOT EXISTS `command_categories` (
  `cmd_category_id` int(11) NOT NULL auto_increment,
  `category_name` varchar(255) NOT NULL,
  `category_alias` varchar(255) NOT NULL,
  `category_order` int(11) NOT NULL,
  PRIMARY KEY  (`cmd_category_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `command_categories_relation`
--

CREATE TABLE IF NOT EXISTS `command_categories_relation` (
  `cmd_cat_id` int(11) NOT NULL auto_increment,
  `category_id` int(11) default NULL,
  `command_command_id` int(11) default NULL,
  PRIMARY KEY  (`cmd_cat_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `contact`
--

CREATE TABLE IF NOT EXISTS `contact` (
  `contact_id` int(11) NOT NULL auto_increment,
  `timeperiod_tp_id` int(11) default NULL,
  `timeperiod_tp_id2` int(11) default NULL,
  `contact_name` varchar(200) default NULL,
  `contact_alias` varchar(200) default NULL,
  `contact_passwd` varchar(255) default NULL,
  `contact_lang` varchar(255) default NULL,
  `contact_host_notification_options` varchar(200) default NULL,
  `contact_service_notification_options` varchar(200) default NULL,
  `contact_email` varchar(200) default NULL,
  `contact_pager` varchar(200) default NULL,
  `contact_comment` text,
  `contact_oreon` enum('0','1') default NULL,
  `contact_admin` enum('0','1') default '0',
  `contact_type_msg` enum('txt','html','pdf') default 'txt',
  `contact_activate` enum('0','1') default NULL,
  `contact_auth_type` varchar(255) default '',
  `contact_ldap_dn` varchar(255) default NULL,
  PRIMARY KEY  (`contact_id`),
  KEY `name_index` (`contact_name`),
  KEY `alias_index` (`contact_alias`),
  KEY `tp1_index` (`timeperiod_tp_id`),
  KEY `tp2_index` (`timeperiod_tp_id2`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `contactgroup`
--

CREATE TABLE IF NOT EXISTS `contactgroup` (
  `cg_id` int(11) NOT NULL auto_increment,
  `cg_name` varchar(200) default NULL,
  `cg_alias` varchar(200) default NULL,
  `cg_comment` text,
  `cg_activate` enum('0','1') default NULL,
  PRIMARY KEY  (`cg_id`),
  KEY `name_index` (`cg_name`),
  KEY `alias_index` (`cg_alias`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `contactgroup_contact_relation`
--

CREATE TABLE IF NOT EXISTS `contactgroup_contact_relation` (
  `cgr_id` int(11) NOT NULL auto_increment,
  `contact_contact_id` int(11) default NULL,
  `contactgroup_cg_id` int(11) default NULL,
  PRIMARY KEY  (`cgr_id`),
  KEY `contact_index` (`contact_contact_id`),
  KEY `contactgroup_index` (`contactgroup_cg_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `contactgroup_hostgroup_relation`
--

CREATE TABLE IF NOT EXISTS `contactgroup_hostgroup_relation` (
  `cghgr_id` int(11) NOT NULL auto_increment,
  `contactgroup_cg_id` int(11) default NULL,
  `hostgroup_hg_id` int(11) default NULL,
  PRIMARY KEY  (`cghgr_id`),
  KEY `contactgroup_index` (`contactgroup_cg_id`),
  KEY `hostgroup_index` (`hostgroup_hg_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `contactgroup_host_relation`
--

CREATE TABLE IF NOT EXISTS `contactgroup_host_relation` (
  `cghr_id` int(11) NOT NULL auto_increment,
  `host_host_id` int(11) default NULL,
  `contactgroup_cg_id` int(11) default NULL,
  PRIMARY KEY  (`cghr_id`),
  KEY `host_index` (`host_host_id`),
  KEY `contactgroup_index` (`contactgroup_cg_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `contactgroup_servicegroup_relation`
--

CREATE TABLE IF NOT EXISTS `contactgroup_servicegroup_relation` (
  `cgsgr_id` int(11) NOT NULL auto_increment,
  `servicegroup_sg_id` int(11) default NULL,
  `contactgroup_cg_id` int(11) default NULL,
  PRIMARY KEY  (`cgsgr_id`),
  KEY `servicegroup_index` (`servicegroup_sg_id`),
  KEY `contactgroup_index` (`contactgroup_cg_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `contactgroup_service_relation`
--

CREATE TABLE IF NOT EXISTS `contactgroup_service_relation` (
  `cgsr_id` int(11) NOT NULL auto_increment,
  `contactgroup_cg_id` int(11) default NULL,
  `service_service_id` int(11) default NULL,
  PRIMARY KEY  (`cgsr_id`),
  KEY `contactgroup_index` (`contactgroup_cg_id`),
  KEY `service_index` (`service_service_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `contact_hostcommands_relation`
--

CREATE TABLE IF NOT EXISTS `contact_hostcommands_relation` (
  `chr_id` int(11) NOT NULL auto_increment,
  `contact_contact_id` int(11) default NULL,
  `command_command_id` int(11) default NULL,
  PRIMARY KEY  (`chr_id`),
  KEY `contact_index` (`contact_contact_id`),
  KEY `command_index` (`command_command_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `contact_param`
--

CREATE TABLE IF NOT EXISTS `contact_param` (
  `id` int(4) NOT NULL auto_increment,
  `cp_key` varchar(255) NOT NULL,
  `cp_value` varchar(255) NOT NULL,
  `cp_contact_id` int(11) default NULL,
  PRIMARY KEY  (`id`),
  KEY `contact_id` (`cp_contact_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `contact_servicecommands_relation`
--

CREATE TABLE IF NOT EXISTS `contact_servicecommands_relation` (
  `csc_id` int(11) NOT NULL auto_increment,
  `contact_contact_id` int(11) default NULL,
  `command_command_id` int(11) default NULL,
  PRIMARY KEY  (`csc_id`),
  KEY `contact_index` (`contact_contact_id`),
  KEY `command_index` (`command_command_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `cron_operation`
--

CREATE TABLE IF NOT EXISTS `cron_operation` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(254) default NULL,
  `command` varchar(254) default NULL,
  `time_launch` varchar(254) default NULL,
  `last_modification` int(11) default '0',
  `system` enum('0','1') default NULL,
  `module` enum('0','1') default NULL,
  `activate` enum('0','1') default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `css_color_menu`
--

CREATE TABLE IF NOT EXISTS `css_color_menu` (
  `id_css_color_menu` int(11) NOT NULL auto_increment,
  `menu_nb` int(11) default NULL,
  `css_name` varchar(255) default NULL,
  PRIMARY KEY  (`id_css_color_menu`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `dependency`
--

CREATE TABLE IF NOT EXISTS `dependency` (
  `dep_id` int(11) NOT NULL auto_increment,
  `dep_name` varchar(255) default NULL,
  `dep_description` varchar(255) default NULL,
  `inherits_parent` enum('0','1') default NULL,
  `execution_failure_criteria` varchar(255) default NULL,
  `notification_failure_criteria` varchar(255) default NULL,
  `dep_comment` text,
  PRIMARY KEY  (`dep_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `dependency_hostChild_relation`
--

CREATE TABLE IF NOT EXISTS `dependency_hostChild_relation` (
  `dhcr_id` int(11) NOT NULL auto_increment,
  `dependency_dep_id` int(11) default NULL,
  `host_host_id` int(11) default NULL,
  PRIMARY KEY  (`dhcr_id`),
  KEY `dependency_index` (`dependency_dep_id`),
  KEY `host_index` (`host_host_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `dependency_hostgroupChild_relation`
--

CREATE TABLE IF NOT EXISTS `dependency_hostgroupChild_relation` (
  `dhgcr_id` int(11) NOT NULL auto_increment,
  `dependency_dep_id` int(11) default NULL,
  `hostgroup_hg_id` int(11) default NULL,
  PRIMARY KEY  (`dhgcr_id`),
  KEY `dependency_index` (`dependency_dep_id`),
  KEY `hostgroup_index` (`hostgroup_hg_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `dependency_hostgroupParent_relation`
--

CREATE TABLE IF NOT EXISTS `dependency_hostgroupParent_relation` (
  `dhgpr_id` int(11) NOT NULL auto_increment,
  `dependency_dep_id` int(11) default NULL,
  `hostgroup_hg_id` int(11) default NULL,
  PRIMARY KEY  (`dhgpr_id`),
  KEY `dependency_index` (`dependency_dep_id`),
  KEY `hostgroup_index` (`hostgroup_hg_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `dependency_hostParent_relation`
--

CREATE TABLE IF NOT EXISTS `dependency_hostParent_relation` (
  `dhpr_id` int(11) NOT NULL auto_increment,
  `dependency_dep_id` int(11) default NULL,
  `host_host_id` int(11) default NULL,
  PRIMARY KEY  (`dhpr_id`),
  KEY `dependency_index` (`dependency_dep_id`),
  KEY `host_index` (`host_host_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `dependency_metaserviceChild_relation`
--

CREATE TABLE IF NOT EXISTS `dependency_metaserviceChild_relation` (
  `dmscr_id` int(11) NOT NULL auto_increment,
  `dependency_dep_id` int(11) default NULL,
  `meta_service_meta_id` int(11) default NULL,
  PRIMARY KEY  (`dmscr_id`),
  KEY `dependency_index` (`dependency_dep_id`),
  KEY `meta_service_index` (`meta_service_meta_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `dependency_metaserviceParent_relation`
--

CREATE TABLE IF NOT EXISTS `dependency_metaserviceParent_relation` (
  `dmspr_id` int(11) NOT NULL auto_increment,
  `dependency_dep_id` int(11) default NULL,
  `meta_service_meta_id` int(11) default NULL,
  PRIMARY KEY  (`dmspr_id`),
  KEY `dependency_index` (`dependency_dep_id`),
  KEY `meta_service_index` (`meta_service_meta_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `dependency_serviceChild_relation`
--

CREATE TABLE IF NOT EXISTS `dependency_serviceChild_relation` (
  `dscr_id` int(11) NOT NULL auto_increment,
  `dependency_dep_id` int(11) default NULL,
  `service_service_id` int(11) default NULL,
  `host_host_id` int(11) default NULL,
  PRIMARY KEY  (`dscr_id`),
  KEY `dependency_index` (`dependency_dep_id`),
  KEY `service_index` (`service_service_id`),
  KEY `host_index` (`host_host_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `dependency_servicegroupChild_relation`
--

CREATE TABLE IF NOT EXISTS `dependency_servicegroupChild_relation` (
  `dsgcr_id` int(11) NOT NULL auto_increment,
  `dependency_dep_id` int(11) default NULL,
  `servicegroup_sg_id` int(11) default NULL,
  PRIMARY KEY  (`dsgcr_id`),
  KEY `dependency_index` (`dependency_dep_id`),
  KEY `sg_index` (`servicegroup_sg_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `dependency_servicegroupParent_relation`
--

CREATE TABLE IF NOT EXISTS `dependency_servicegroupParent_relation` (
  `dsgpr_id` int(11) NOT NULL auto_increment,
  `dependency_dep_id` int(11) default NULL,
  `servicegroup_sg_id` int(11) default NULL,
  PRIMARY KEY  (`dsgpr_id`),
  KEY `dependency_index` (`dependency_dep_id`),
  KEY `sg_index` (`servicegroup_sg_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `dependency_serviceParent_relation`
--

CREATE TABLE IF NOT EXISTS `dependency_serviceParent_relation` (
  `dspr_id` int(11) NOT NULL auto_increment,
  `dependency_dep_id` int(11) default NULL,
  `service_service_id` int(11) default NULL,
  `host_host_id` int(11) default NULL,
  PRIMARY KEY  (`dspr_id`),
  KEY `dependency_index` (`dependency_dep_id`),
  KEY `service_index` (`service_service_id`),
  KEY `host_index` (`host_host_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `escalation`
--

CREATE TABLE IF NOT EXISTS `escalation` (
  `esc_id` int(11) NOT NULL auto_increment,
  `esc_name` varchar(255) default NULL,
  `esc_alias` varchar(255) default NULL,
  `first_notification` int(11) default NULL,
  `last_notification` int(11) default NULL,
  `notification_interval` int(11) default NULL,
  `escalation_period` int(11) default NULL,
  `escalation_options1` varchar(255) default NULL,
  `escalation_options2` varchar(255) default NULL,
  `esc_comment` text,
  PRIMARY KEY  (`esc_id`),
  KEY `period_index` (`escalation_period`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `escalation_contactgroup_relation`
--

CREATE TABLE IF NOT EXISTS `escalation_contactgroup_relation` (
  `ecgr_id` int(11) NOT NULL auto_increment,
  `escalation_esc_id` int(11) default NULL,
  `contactgroup_cg_id` int(11) default NULL,
  PRIMARY KEY  (`ecgr_id`),
  KEY `escalation_index` (`escalation_esc_id`),
  KEY `cg_index` (`contactgroup_cg_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `escalation_hostgroup_relation`
--

CREATE TABLE IF NOT EXISTS `escalation_hostgroup_relation` (
  `ehgr_id` int(11) NOT NULL auto_increment,
  `escalation_esc_id` int(11) default NULL,
  `hostgroup_hg_id` int(11) default NULL,
  PRIMARY KEY  (`ehgr_id`),
  KEY `escalation_index` (`escalation_esc_id`),
  KEY `hg_index` (`hostgroup_hg_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `escalation_host_relation`
--

CREATE TABLE IF NOT EXISTS `escalation_host_relation` (
  `ehr_id` int(11) NOT NULL auto_increment,
  `escalation_esc_id` int(11) default NULL,
  `host_host_id` int(11) default NULL,
  PRIMARY KEY  (`ehr_id`),
  KEY `escalation_index` (`escalation_esc_id`),
  KEY `host_index` (`host_host_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `escalation_meta_service_relation`
--

CREATE TABLE IF NOT EXISTS `escalation_meta_service_relation` (
  `emsr_id` int(11) NOT NULL auto_increment,
  `escalation_esc_id` int(11) default NULL,
  `meta_service_meta_id` int(11) default NULL,
  PRIMARY KEY  (`emsr_id`),
  KEY `escalation_index` (`escalation_esc_id`),
  KEY `meta_service_index` (`meta_service_meta_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `escalation_servicegroup_relation`
--

CREATE TABLE IF NOT EXISTS `escalation_servicegroup_relation` (
  `esgr_id` int(11) NOT NULL auto_increment,
  `escalation_esc_id` int(11) default NULL,
  `servicegroup_sg_id` int(11) default NULL,
  PRIMARY KEY  (`esgr_id`),
  KEY `escalation_index` (`escalation_esc_id`),
  KEY `sg_index` (`servicegroup_sg_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `escalation_service_relation`
--

CREATE TABLE IF NOT EXISTS `escalation_service_relation` (
  `esr_id` int(11) NOT NULL auto_increment,
  `escalation_esc_id` int(11) default NULL,
  `service_service_id` int(11) default NULL,
  `host_host_id` int(11) default NULL,
  PRIMARY KEY  (`esr_id`),
  KEY `escalation_index` (`escalation_esc_id`),
  KEY `service_index` (`service_service_id`),
  KEY `host_index` (`host_host_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `extended_host_information`
--

CREATE TABLE IF NOT EXISTS `extended_host_information` (
  `ehi_id` int(11) NOT NULL auto_increment,
  `host_host_id` int(11) default NULL,
  `ehi_notes` varchar(200) default NULL,
  `ehi_notes_url` varchar(200) default NULL,
  `ehi_action_url` varchar(200) default NULL,
  `ehi_icon_image` int(11) default NULL,
  `ehi_icon_image_alt` varchar(200) default NULL,
  `ehi_vrml_image` int(11) default NULL,
  `ehi_statusmap_image` int(11) default NULL,
  `ehi_2d_coords` varchar(200) default NULL,
  `ehi_3d_coords` varchar(200) default NULL,
  PRIMARY KEY  (`ehi_id`),
  KEY `host_index` (`host_host_id`),
  KEY `extended_host_information_ibfk_2` (`ehi_icon_image`),
  KEY `extended_host_information_ibfk_3` (`ehi_vrml_image`),
  KEY `extended_host_information_ibfk_4` (`ehi_statusmap_image`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `extended_service_information`
--

CREATE TABLE IF NOT EXISTS `extended_service_information` (
  `esi_id` int(11) NOT NULL auto_increment,
  `service_service_id` int(11) default NULL,
  `esi_notes` varchar(200) default NULL,
  `esi_notes_url` varchar(200) default NULL,
  `esi_action_url` varchar(200) default NULL,
  `esi_icon_image` int(11) default NULL,
  `esi_icon_image_alt` varchar(200) default NULL,
  `graph_id` int(11) default NULL,
  PRIMARY KEY  (`esi_id`),
  KEY `service_index` (`service_service_id`),
  KEY `graph_index` (`graph_id`),
  KEY `extended_service_information_ibfk_3` (`esi_icon_image`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `general_opt`
--

CREATE TABLE IF NOT EXISTS `general_opt` (
  `gopt_id` int(11) NOT NULL auto_increment,
  `nagios_path` varchar(255) default NULL,
  `nagios_path_bin` varchar(255) default NULL,
  `nagios_init_script` varchar(255) default NULL,
  `nagios_path_img` varchar(255) default NULL,
  `nagios_path_plugins` varchar(255) default NULL,
  `nagios_version` enum('1','2','3') default NULL,
  `snmp_community` varchar(255) default NULL,
  `snmp_version` varchar(255) default NULL,
  `snmpttconvertmib_path_bin` varchar(255) default NULL,
  `perl_library_path` varchar(255) default NULL,
  `snmp_trapd_path_conf` varchar(255) default NULL,
  `mailer_path_bin` varchar(255) default NULL,
  `rrdtool_path_bin` varchar(255) default NULL,
  `rrdtool_version` varchar(255) default NULL,
  `oreon_path` varchar(255) default NULL,
  `oreon_web_path` varchar(255) default NULL,
  `oreon_rrdbase_path` varchar(255) default NULL,
  `oreon_refresh` int(11) default NULL,
  `color_up` varchar(50) default NULL,
  `color_down` varchar(50) default NULL,
  `color_unreachable` varchar(50) default NULL,
  `color_ok` varchar(50) default NULL,
  `color_warning` varchar(50) default NULL,
  `color_critical` varchar(50) default NULL,
  `color_pending` varchar(50) default NULL,
  `color_unknown` varchar(50) default NULL,
  `session_expire` int(11) default NULL,
  `perfparse_installed` enum('0','1') default NULL,
  `graph_preferencies` int(11) default '0',
  `maxViewMonitoring` int(11) NOT NULL default '50',
  `maxViewConfiguration` int(11) NOT NULL default '20',
  `AjaxTimeReloadMonitoring` int(11) NOT NULL default '15',
  `AjaxTimeReloadStatistic` int(11) NOT NULL default '15',
  `AjaxFirstTimeReloadMonitoring` int(11) NOT NULL default '15',
  `AjaxFirstTimeReloadStatistic` int(11) NOT NULL default '1',
  `template` varchar(254) default 'Basic',
  `problem_sort_type` varchar(25) default NULL,
  `problem_sort_order` varchar(25) default NULL,
  `ldap_host` varchar(254) default NULL,
  `ldap_port` varchar(5) default '389',
  `ldap_base_dn` varchar(254) default NULL,
  `ldap_login_attrib` varchar(254) default 'dn',
  `ldap_ssl` enum('0','1') default NULL,
  `ldap_search_user` varchar(254) default NULL,
  `ldap_search_user_pwd` varchar(254) default NULL,
  `ldap_search_filter` varchar(254) default NULL,
  `ldap_search_timeout` varchar(5) default '60',
  `ldap_search_limit` varchar(5) default '60',
  `ldap_auth_enable` enum('0','1') default NULL,
  `debug_path` varchar(255) default NULL,
  `debug_auth` enum('0','1') default NULL,
  `debug_nagios_import` enum('0','1') default NULL,
  `debug_rrdtool` enum('0','1') default NULL,
  `debug_ldap_import` enum('0','1') default NULL,
  `debug_inventory` enum('0','1') default NULL,
  `gmt` int(11) NOT NULL default '0',
  `patch_type_stable` enum('Y','N') default 'Y',
  `patch_type_RC` enum('Y','N') default 'N',
  `patch_type_patch` enum('Y','N') default 'N',
  `patch_type_secu` enum('Y','N') default 'Y',
  `patch_type_beta` enum('Y','N') default 'N',
  `patch_url_service` varchar(255) default NULL,
  `patch_url_download` varchar(255) default NULL,
  `patch_path_download` varchar(255) default NULL,
  `ndo_activate` binary(1) default NULL,
  `snmptt_unknowntrap_log_file` varchar(255) default NULL,
  PRIMARY KEY  (`gopt_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `giv_components_template`
--

CREATE TABLE IF NOT EXISTS `giv_components_template` (
  `compo_id` int(11) NOT NULL auto_increment,
  `name` varchar(255) default NULL,
  `ds_order` int(11) default NULL,
  `ds_name` varchar(200) default NULL,
  `ds_color_line` varchar(255) default NULL,
  `ds_color_area` varchar(255) default NULL,
  `ds_filled` enum('0','1') default NULL,
  `ds_max` enum('0','1') default NULL,
  `ds_min` enum('0','1') default NULL,
  `ds_average` enum('0','1') default NULL,
  `ds_last` enum('0','1') default NULL,
  `ds_tickness` int(11) default NULL,
  `ds_transparency` varchar(254) default NULL,
  `ds_invert` enum('0','1') default NULL,
  `default_tpl1` enum('0','1') default NULL,
  `comment` text,
  PRIMARY KEY  (`compo_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `giv_graphs_template`
--

CREATE TABLE IF NOT EXISTS `giv_graphs_template` (
  `graph_id` int(11) NOT NULL auto_increment,
  `name` varchar(200) default NULL,
  `vertical_label` varchar(200) default NULL,
  `width` int(11) default NULL,
  `height` int(11) default NULL,
  `base` int(11) default '1000',
  `lower_limit` float default NULL,
  `upper_limit` float default NULL,
  `bg_grid_color` varchar(200) default NULL,
  `bg_color` varchar(200) default NULL,
  `police_color` varchar(200) default NULL,
  `grid_main_color` varchar(200) default NULL,
  `grid_sec_color` varchar(200) default NULL,
  `contour_cub_color` varchar(200) default NULL,
  `col_arrow` varchar(200) default NULL,
  `col_top` varchar(200) default NULL,
  `col_bot` varchar(200) default NULL,
  `default_tpl1` enum('0','1') default NULL,
  `stacked` enum('0','1') default NULL,
  `split_component` enum('0','1') default '0',
  `comment` text,
  PRIMARY KEY  (`graph_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `host`
--

CREATE TABLE IF NOT EXISTS `host` (
  `host_id` int(11) NOT NULL auto_increment,
  `host_template_model_htm_id` int(11) default NULL,
  `command_command_id` int(11) default NULL,
  `command_command_id_arg1` text,
  `timeperiod_tp_id` int(11) default NULL,
  `timeperiod_tp_id2` int(11) default NULL,
  `purge_policy_id` int(11) default NULL,
  `command_command_id2` int(11) default NULL,
  `command_command_id_arg2` text,
  `host_name` varchar(200) default NULL,
  `host_alias` varchar(200) default NULL,
  `host_address` varchar(255) default NULL,
  `host_max_check_attempts` int(11) default NULL,
  `host_check_interval` int(11) default NULL,
  `host_active_checks_enabled` enum('0','1','2') default NULL,
  `host_passive_checks_enabled` enum('0','1','2') default NULL,
  `host_checks_enabled` enum('0','1','2') default NULL,
  `host_obsess_over_host` enum('0','1','2') default NULL,
  `host_check_freshness` enum('0','1','2') default NULL,
  `host_freshness_threshold` int(11) default NULL,
  `host_event_handler_enabled` enum('0','1','2') default NULL,
  `host_low_flap_threshold` int(11) default NULL,
  `host_high_flap_threshold` int(11) default NULL,
  `host_flap_detection_enabled` enum('0','1','2') default NULL,
  `host_process_perf_data` enum('0','1','2') default NULL,
  `host_retain_status_information` enum('0','1','2') default NULL,
  `host_retain_nonstatus_information` enum('0','1','2') default NULL,
  `host_notification_interval` int(11) default NULL,
  `host_notification_options` varchar(200) default NULL,
  `host_notifications_enabled` enum('0','1','2') default NULL,
  `host_stalking_options` varchar(200) default NULL,
  `host_snmp_community` varchar(255) default NULL,
  `host_snmp_version` varchar(255) default NULL,
  `host_comment` text,
  `host_register` enum('0','1') default NULL,
  `host_activate` enum('0','1') default '1',
  PRIMARY KEY  (`host_id`),
  KEY `htm_index` (`host_template_model_htm_id`),
  KEY `cmd1_index` (`command_command_id`),
  KEY `cmd2_index` (`command_command_id2`),
  KEY `tp1_index` (`timeperiod_tp_id`),
  KEY `tp2_index` (`timeperiod_tp_id2`),
  KEY `name_index` (`host_name`),
  KEY `alias_index` (`host_alias`),
  KEY `purge_index` (`purge_policy_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `hostgroup`
--

CREATE TABLE IF NOT EXISTS `hostgroup` (
  `hg_id` int(11) NOT NULL auto_increment,
  `hg_name` varchar(200) default NULL,
  `hg_alias` varchar(200) default NULL,
  `hg_snmp_community` varchar(255) default NULL,
  `hg_snmp_version` varchar(255) default NULL,
  `hg_comment` text,
  `hg_activate` enum('0','1') NOT NULL default '1',
  PRIMARY KEY  (`hg_id`),
  KEY `name_index` (`hg_name`),
  KEY `alias_index` (`hg_alias`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `hostgroup_relation`
--

CREATE TABLE IF NOT EXISTS `hostgroup_relation` (
  `hgr_id` int(11) NOT NULL auto_increment,
  `hostgroup_hg_id` int(11) default NULL,
  `host_host_id` int(11) default NULL,
  PRIMARY KEY  (`hgr_id`),
  KEY `hostgroup_index` (`hostgroup_hg_id`),
  KEY `host_index` (`host_host_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `host_hostparent_relation`
--

CREATE TABLE IF NOT EXISTS `host_hostparent_relation` (
  `hhr_id` int(11) NOT NULL auto_increment,
  `host_parent_hp_id` int(11) default NULL,
  `host_host_id` int(11) default NULL,
  PRIMARY KEY  (`hhr_id`),
  KEY `host1_index` (`host_parent_hp_id`),
  KEY `host2_index` (`host_host_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `host_service_relation`
--

CREATE TABLE IF NOT EXISTS `host_service_relation` (
  `hsr_id` int(11) NOT NULL auto_increment,
  `hostgroup_hg_id` int(11) default NULL,
  `host_host_id` int(11) default NULL,
  `servicegroup_sg_id` int(11) default NULL,
  `service_service_id` int(11) default NULL,
  PRIMARY KEY  (`hsr_id`),
  KEY `hostgroup_index` (`hostgroup_hg_id`),
  KEY `host_index` (`host_host_id`),
  KEY `servicegroup_index` (`servicegroup_sg_id`),
  KEY `service_index` (`service_service_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `informations`
--

CREATE TABLE IF NOT EXISTS `informations` (
  `key` varchar(25) default NULL,
  `value` varchar(25) default NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `meta_contactgroup_relation`
--

CREATE TABLE IF NOT EXISTS `meta_contactgroup_relation` (
  `mcr_id` int(11) NOT NULL auto_increment,
  `meta_id` int(11) default NULL,
  `cg_cg_id` int(11) default NULL,
  PRIMARY KEY  (`mcr_id`),
  KEY `meta_index` (`meta_id`),
  KEY `cg_index` (`cg_cg_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `meta_service`
--

CREATE TABLE IF NOT EXISTS `meta_service` (
  `meta_id` int(11) NOT NULL auto_increment,
  `meta_name` varchar(254) default NULL,
  `meta_display` varchar(254) default NULL,
  `check_period` int(11) default NULL,
  `max_check_attempts` int(11) default NULL,
  `normal_check_interval` int(11) default NULL,
  `retry_check_interval` int(11) default NULL,
  `notification_interval` int(11) default NULL,
  `notification_period` int(11) default NULL,
  `notification_options` varchar(255) default NULL,
  `notifications_enabled` enum('0','1','2') default NULL,
  `calcul_type` enum('SOM','AVE','MIN','MAX') default NULL,
  `meta_select_mode` enum('1','2') default '1',
  `regexp_str` varchar(254) default NULL,
  `metric` varchar(255) default NULL,
  `warning` varchar(254) default NULL,
  `critical` varchar(254) default NULL,
  `graph_id` int(11) default NULL,
  `meta_comment` text,
  `meta_activate` enum('0','1') default NULL,
  PRIMARY KEY  (`meta_id`),
  KEY `name_index` (`meta_name`),
  KEY `check_period_index` (`check_period`),
  KEY `notification_period_index` (`notification_period`),
  KEY `graph_index` (`graph_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `meta_service_relation`
--

CREATE TABLE IF NOT EXISTS `meta_service_relation` (
  `msr_id` int(11) NOT NULL auto_increment,
  `meta_id` int(11) default NULL,
  `host_id` int(11) default NULL,
  `metric_id` int(11) default NULL,
  `msr_comment` text,
  `activate` enum('0','1') default NULL,
  PRIMARY KEY  (`msr_id`),
  KEY `meta_index` (`meta_id`),
  KEY `metric_index` (`metric_id`),
  KEY `host_index` (`host_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `modules_informations`
--

CREATE TABLE IF NOT EXISTS `modules_informations` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) default NULL,
  `rname` varchar(255) default NULL,
  `mod_release` varchar(255) default NULL,
  `is_removeable` enum('0','1') default NULL,
  `infos` text,
  `author` varchar(255) default NULL,
  `lang_files` enum('0','1') default NULL,
  `sql_files` enum('0','1') default NULL,
  `php_files` enum('0','1') default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `nagios_macro`
--

CREATE TABLE IF NOT EXISTS `nagios_macro` (
  `macro_id` int(11) NOT NULL auto_increment,
  `macro_name` varchar(255) default NULL,
  PRIMARY KEY  (`macro_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `nagios_server`
--

CREATE TABLE IF NOT EXISTS `nagios_server` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(40) default NULL,
  `localhost` enum('0','1') default NULL,
  `last_restart` int(11) default NULL,
  `ns_ip_address` varchar(255) default NULL,
  `ns_activate` enum('1','0') default '1',
  `ns_status` enum('0','1','2','3','4') default '0',
  `init_script` varchar(255) default NULL,
  `nagios_bin` varchar(255) default NULL,
  `nagiosstats_bin` varchar(255) default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `ns_host_relation`
--

CREATE TABLE IF NOT EXISTS `ns_host_relation` (
  `nagios_server_id` int(11) NOT NULL default '0',
  `host_host_id` int(11) NOT NULL default '0',
  PRIMARY KEY  (`nagios_server_id`,`host_host_id`),
  KEY `host_host_id` (`host_host_id`),
  KEY `nagios_server_id` (`nagios_server_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COMMENT='Relation Table For centreon Servers and hosts ';

-- --------------------------------------------------------

--
-- Structure de la table `ods_view_details`
--

CREATE TABLE IF NOT EXISTS `ods_view_details` (
  `dv_id` int(11) NOT NULL auto_increment,
  `index_id` int(11) default NULL,
  `metric_id` int(11) default NULL,
  `contact_id` int(11) default NULL,
  `all_user` enum('0','1') default NULL,
  PRIMARY KEY  (`dv_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `purge_policy`
--

CREATE TABLE IF NOT EXISTS `purge_policy` (
  `purge_policy_id` int(11) NOT NULL auto_increment,
  `purge_policy_name` varchar(255) default NULL,
  `purge_policy_alias` varchar(255) default NULL,
  `purge_policy_retention` int(11) default NULL,
  `purge_policy_raw` enum('0','1') default '0',
  `purge_policy_bin` enum('0','1') default '0',
  `purge_policy_metric` enum('0','1') default '0',
  `purge_policy_service` enum('0','1') default '0',
  `purge_policy_host` enum('0','1') default '0',
  `purge_policy_comment` text,
  PRIMARY KEY  (`purge_policy_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `reporting_diff_email`
--

CREATE TABLE IF NOT EXISTS `reporting_diff_email` (
  `rtde_id` int(11) NOT NULL auto_increment,
  `email` varchar(255) default NULL,
  `format` enum('1','2') default '2',
  `comment` text,
  `activate` enum('0','1') default NULL,
  PRIMARY KEY  (`rtde_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `reporting_diff_list`
--

CREATE TABLE IF NOT EXISTS `reporting_diff_list` (
  `rtdl_id` int(11) NOT NULL auto_increment,
  `name` varchar(255) default NULL,
  `description` varchar(255) default NULL,
  `tp_id` int(11) default NULL,
  `activate` enum('0','1') default NULL,
  `comment` text,
  PRIMARY KEY  (`rtdl_id`),
  KEY `timeperiod_index` (`tp_id`),
  KEY `name_index` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `reporting_email_list_relation`
--

CREATE TABLE IF NOT EXISTS `reporting_email_list_relation` (
  `rtelr_id` int(11) NOT NULL auto_increment,
  `rtdl_id` int(11) default NULL,
  `rtde_id` int(11) default NULL,
  `oreon_contact` enum('0','1') default '0',
  PRIMARY KEY  (`rtelr_id`),
  KEY `list_index` (`rtdl_id`),
  KEY `email_index` (`rtde_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `service`
--

CREATE TABLE IF NOT EXISTS `service` (
  `service_id` int(11) NOT NULL auto_increment,
  `service_template_model_stm_id` int(11) default NULL,
  `command_command_id` int(11) default NULL,
  `timeperiod_tp_id` int(11) default NULL,
  `command_command_id2` int(11) default NULL,
  `timeperiod_tp_id2` int(11) default NULL,
  `purge_policy_id` int(11) default NULL,
  `service_description` varchar(200) default NULL,
  `service_alias` varchar(255) default NULL,
  `service_is_volatile` enum('0','1','2') default '2',
  `service_max_check_attempts` int(11) default NULL,
  `service_normal_check_interval` int(11) default NULL,
  `service_retry_check_interval` int(11) default NULL,
  `service_active_checks_enabled` enum('0','1','2') default '2',
  `service_passive_checks_enabled` enum('0','1','2') default '2',
  `service_parallelize_check` enum('0','1','2') default '2',
  `service_obsess_over_service` enum('0','1','2') default '2',
  `service_check_freshness` enum('0','1','2') default '2',
  `service_freshness_threshold` int(11) default NULL,
  `service_event_handler_enabled` enum('0','1','2') default '2',
  `service_low_flap_threshold` int(11) default NULL,
  `service_high_flap_threshold` int(11) default NULL,
  `service_flap_detection_enabled` enum('0','1','2') default '2',
  `service_process_perf_data` enum('0','1','2') default '2',
  `service_retain_status_information` enum('0','1','2') default '2',
  `service_retain_nonstatus_information` enum('0','1','2') default '2',
  `service_notification_interval` int(11) default NULL,
  `service_notification_options` varchar(200) default NULL,
  `service_notifications_enabled` enum('0','1','2') default '2',
  `service_stalking_options` varchar(200) default NULL,
  `service_comment` text,
  `command_command_id_arg` text,
  `command_command_id_arg2` text,
  `service_register` enum('0','1') NOT NULL default '0',
  `service_activate` enum('0','1') NOT NULL default '1',
  PRIMARY KEY  (`service_id`),
  KEY `stm_index` (`service_template_model_stm_id`),
  KEY `cmd1_index` (`command_command_id`),
  KEY `cmd2_index` (`command_command_id2`),
  KEY `tp1_index` (`timeperiod_tp_id`),
  KEY `tp2_index` (`timeperiod_tp_id2`),
  KEY `description_index` (`service_description`),
  KEY `purge_index` (`purge_policy_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `servicegroup`
--

CREATE TABLE IF NOT EXISTS `servicegroup` (
  `sg_id` int(11) NOT NULL auto_increment,
  `sg_name` varchar(200) default NULL,
  `sg_alias` varchar(200) default NULL,
  `sg_comment` text,
  `sg_activate` enum('0','1') NOT NULL default '1',
  PRIMARY KEY  (`sg_id`),
  KEY `name_index` (`sg_name`),
  KEY `alias_index` (`sg_alias`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `servicegroup_relation`
--

CREATE TABLE IF NOT EXISTS `servicegroup_relation` (
  `sgr_id` int(11) NOT NULL auto_increment,
  `host_host_id` int(11) default NULL,
  `hostgroup_hg_id` int(11) default NULL,
  `service_service_id` int(11) default NULL,
  `servicegroup_sg_id` int(11) default NULL,
  PRIMARY KEY  (`sgr_id`),
  KEY `service_index` (`service_service_id`),
  KEY `servicegroup_index` (`servicegroup_sg_id`),
  KEY `host_host_id` (`host_host_id`),
  KEY `hostgroup_hg_id` (`hostgroup_hg_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `service_categories`
--

CREATE TABLE IF NOT EXISTS `service_categories` (
  `sc_id` int(11) NOT NULL auto_increment,
  `sc_name` varchar(255) default NULL,
  `sc_description` varchar(255) default NULL,
  `sc_activate` enum('0','1') default NULL,
  PRIMARY KEY  (`sc_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 COMMENT='Services Catygories For best Reporting' AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `service_categories_relation`
--

CREATE TABLE IF NOT EXISTS `service_categories_relation` (
  `scr_id` int(11) NOT NULL auto_increment,
  `service_service_id` int(11) default NULL,
  `sc_id` int(11) default NULL,
  PRIMARY KEY  (`scr_id`),
  KEY `service_service_id` (`service_service_id`),
  KEY `sc_id` (`sc_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `session`
--

CREATE TABLE IF NOT EXISTS `session` (
  `id` int(11) NOT NULL auto_increment,
  `session_id` varchar(40) default NULL,
  `user_id` int(11) default NULL,
  `current_page` int(11) default NULL,
  `last_reload` int(11) default NULL,
  `ip_address` varchar(16) default NULL,
  `s_nbHostsUp` int(11) default NULL,
  `s_nbHostsDown` int(11) default NULL,
  `s_nbHostsUnreachable` int(11) default NULL,
  `s_nbHostsPending` int(11) default NULL,
  `s_nbServicesOk` int(11) default NULL,
  `s_nbServicesWarning` int(11) default NULL,
  `s_nbServicesCritical` int(11) default NULL,
  `s_nbServicesPending` int(11) default NULL,
  `s_nbServicesUnknown` int(11) default NULL,
  PRIMARY KEY  (`id`),
  KEY `session_id` (`session_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `timeperiod`
--

CREATE TABLE IF NOT EXISTS `timeperiod` (
  `tp_id` int(11) NOT NULL auto_increment,
  `tp_name` varchar(200) default NULL,
  `tp_alias` varchar(200) default NULL,
  `tp_sunday` varchar(200) default NULL,
  `tp_monday` varchar(200) default NULL,
  `tp_tuesday` varchar(200) default NULL,
  `tp_wednesday` varchar(200) default NULL,
  `tp_thursday` varchar(200) default NULL,
  `tp_friday` varchar(200) default NULL,
  `tp_saturday` varchar(200) default NULL,
  PRIMARY KEY  (`tp_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `topology`
--

CREATE TABLE IF NOT EXISTS `topology` (
  `topology_id` int(11) NOT NULL auto_increment,
  `topology_name` varchar(255) default NULL,
  `topology_icone` varchar(255) default NULL,
  `topology_parent` int(11) default NULL,
  `topology_page` int(11) default NULL,
  `topology_order` int(11) default NULL,
  `topology_group` int(11) default NULL,
  `topology_url` varchar(255) default NULL,
  `topology_url_opt` varchar(255) default NULL,
  `topology_popup` enum('0','1') default NULL,
  `topology_modules` enum('0','1') default NULL,
  `topology_show` enum('0','1') default '1',
  `topology_style_class` varchar(255) default NULL,
  `topology_style_id` varchar(255) default NULL,
  `topology_OnClick` varchar(255) default NULL,
  PRIMARY KEY  (`topology_id`),
  KEY `topology_page` (`topology_page`),
  KEY `topology_parent` (`topology_parent`),
  KEY `topology_order` (`topology_order`),
  KEY `topology_group` (`topology_group`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `topology_JS`
--

CREATE TABLE IF NOT EXISTS `topology_JS` (
  `id_t_js` int(11) NOT NULL auto_increment,
  `id_page` int(11) default NULL,
  `o` varchar(12) default NULL,
  `PathName_js` text,
  `Init` text,
  PRIMARY KEY  (`id_t_js`),
  KEY `id_page` (`id_page`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `traps`
--

CREATE TABLE IF NOT EXISTS `traps` (
  `traps_id` int(11) NOT NULL auto_increment,
  `traps_name` varchar(255) default NULL,
  `traps_oid` varchar(255) default NULL,
  `traps_args` varchar(255) default NULL,
  `traps_status` enum('-1','0','1','2','3') default NULL,
  `manufacturer_id` int(11) default NULL,
  `traps_reschedule_svc_enable` enum('0','1') default 0,
  `traps_execution_command` varchar(255) default NULL,
  `traps_execution_command_enable` enum('0','1') default 0,
  `traps_submit_result_enable` enum('0','1') default 1,
  `traps_comments` text,
  UNIQUE KEY `traps_name` (`traps_name`),
  KEY `traps_id` (`traps_id`),
  KEY `traps_ibfk_1` (`manufacturer_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `traps_service_relation`
--

CREATE TABLE IF NOT EXISTS `traps_service_relation` (
  `tsr_id` int(11) NOT NULL auto_increment,
  `traps_id` int(11) default NULL,
  `service_id` int(11) default NULL,
  PRIMARY KEY  (`tsr_id`),
  KEY `service_index` (`service_id`),
  KEY `traps_index` (`traps_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `traps_vendor`
--

CREATE TABLE IF NOT EXISTS `traps_vendor` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(254) default NULL,
  `alias` varchar(254) default NULL,
  `description` text,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `view_img`
--

CREATE TABLE IF NOT EXISTS `view_img` (
  `img_id` int(11) NOT NULL auto_increment,
  `img_name` varchar(255) default NULL,
  `img_path` varchar(255) default NULL,
  `img_comment` text,
  PRIMARY KEY  (`img_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `view_img_dir`
--

CREATE TABLE IF NOT EXISTS `view_img_dir` (
  `dir_id` int(11) NOT NULL auto_increment,
  `dir_name` varchar(255) default NULL,
  `dir_alias` varchar(255) default NULL,
  `dir_comment` text,
  PRIMARY KEY  (`dir_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Structure de la table `view_img_dir_relation`
--

CREATE TABLE IF NOT EXISTS `view_img_dir_relation` (
  `vidr_id` int(11) NOT NULL auto_increment,
  `dir_dir_parent_id` int(11) default NULL,
  `img_img_id` int(11) default NULL,
  PRIMARY KEY  (`vidr_id`),
  KEY `directory_parent_index` (`dir_dir_parent_id`),
  KEY `image_index` (`img_img_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

--
-- Contraintes pour les tables export�es
--

--
-- Contraintes pour la table `acl_group_contacts_relations`
--
ALTER TABLE `acl_group_contacts_relations`
  ADD CONSTRAINT `acl_group_contacts_relations_ibfk_1` FOREIGN KEY (`contact_contact_id`) REFERENCES `contact` (`contact_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `acl_group_contacts_relations_ibfk_2` FOREIGN KEY (`acl_group_id`) REFERENCES `acl_groups` (`acl_group_id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `acl_group_topology_relations`
--
ALTER TABLE `acl_group_topology_relations`
  ADD CONSTRAINT `acl_group_topology_relations_ibfk_1` FOREIGN KEY (`acl_group_id`) REFERENCES `acl_groups` (`acl_group_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `acl_group_topology_relations_ibfk_2` FOREIGN KEY (`acl_topology_id`) REFERENCES `acl_topology` (`acl_topo_id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `acl_resources_hostex_relations`
--
ALTER TABLE `acl_resources_hostex_relations`
  ADD CONSTRAINT `acl_resources_hostex_relations_ibfk_1` FOREIGN KEY (`host_host_id`) REFERENCES `host` (`host_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `acl_resources_hostex_relations_ibfk_2` FOREIGN KEY (`acl_res_id`) REFERENCES `acl_resources` (`acl_res_id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `acl_resources_host_relations`
--
ALTER TABLE `acl_resources_host_relations`
  ADD CONSTRAINT `acl_resources_host_relations_ibfk_1` FOREIGN KEY (`host_host_id`) REFERENCES `host` (`host_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `acl_resources_host_relations_ibfk_2` FOREIGN KEY (`acl_res_id`) REFERENCES `acl_resources` (`acl_res_id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `acl_resources_sc_relations`
--
ALTER TABLE `acl_resources_sc_relations`
  ADD CONSTRAINT `acl_resources_sc_relations_ibfk_1` FOREIGN KEY (`sc_id`) REFERENCES `service_categories` (`sc_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `acl_resources_sc_relations_ibfk_2` FOREIGN KEY (`acl_res_id`) REFERENCES `acl_resources` (`acl_res_id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `acl_resources_service_relations`
--
ALTER TABLE `acl_resources_service_relations`
  ADD CONSTRAINT `acl_resources_service_relations_ibfk_1` FOREIGN KEY (`service_service_id`) REFERENCES `service` (`service_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `acl_resources_service_relations_ibfk_2` FOREIGN KEY (`acl_group_id`) REFERENCES `acl_groups` (`acl_group_id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `acl_res_group_relations`
--
ALTER TABLE `acl_res_group_relations`
  ADD CONSTRAINT `acl_res_group_relations_ibfk_1` FOREIGN KEY (`acl_res_id`) REFERENCES `acl_resources` (`acl_res_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `acl_res_group_relations_ibfk_2` FOREIGN KEY (`acl_group_id`) REFERENCES `acl_groups` (`acl_group_id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `acl_topology_relations`
--
ALTER TABLE `acl_topology_relations`
  ADD CONSTRAINT `acl_topology_relations_ibfk_2` FOREIGN KEY (`topology_topology_id`) REFERENCES `topology` (`topology_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `acl_topology_relations_ibfk_3` FOREIGN KEY (`acl_topo_id`) REFERENCES `acl_topology` (`acl_topo_id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `cfg_nagios`
--
ALTER TABLE `cfg_nagios`
  ADD CONSTRAINT `cfg_nagios_ibfk_15` FOREIGN KEY (`service_perfdata_command`) REFERENCES `command` (`command_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `cfg_nagios_ibfk_18` FOREIGN KEY (`global_host_event_handler`) REFERENCES `command` (`command_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `cfg_nagios_ibfk_19` FOREIGN KEY (`global_service_event_handler`) REFERENCES `command` (`command_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `cfg_nagios_ibfk_20` FOREIGN KEY (`ocsp_command`) REFERENCES `command` (`command_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `cfg_nagios_ibfk_21` FOREIGN KEY (`ochp_command`) REFERENCES `command` (`command_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `cfg_nagios_ibfk_22` FOREIGN KEY (`host_perfdata_command`) REFERENCES `command` (`command_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `cfg_nagios_ibfk_23` FOREIGN KEY (`service_perfdata_command`) REFERENCES `command` (`command_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `cfg_nagios_ibfk_24` FOREIGN KEY (`host_perfdata_file_processing_command`) REFERENCES `command` (`command_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `cfg_nagios_ibfk_25` FOREIGN KEY (`service_perfdata_file_processing_command`) REFERENCES `command` (`command_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `cfg_nagios_ibfk_26` FOREIGN KEY (`nagios_server_id`) REFERENCES `nagios_server` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `cfg_ndo2db`
--
ALTER TABLE `cfg_ndo2db`
  ADD CONSTRAINT `cfg_ndo2db_ibfk_1` FOREIGN KEY (`ns_nagios_server`) REFERENCES `nagios_server` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `cfg_ndomod`
--
ALTER TABLE `cfg_ndomod`
  ADD CONSTRAINT `cfg_ndomod_ibfk_1` FOREIGN KEY (`ns_nagios_server`) REFERENCES `nagios_server` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `contact`
--
ALTER TABLE `contact`
  ADD CONSTRAINT `contact_ibfk_1` FOREIGN KEY (`timeperiod_tp_id`) REFERENCES `timeperiod` (`tp_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `contact_ibfk_2` FOREIGN KEY (`timeperiod_tp_id2`) REFERENCES `timeperiod` (`tp_id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `contactgroup_contact_relation`
--
ALTER TABLE `contactgroup_contact_relation`
  ADD CONSTRAINT `contactgroup_contact_relation_ibfk_1` FOREIGN KEY (`contact_contact_id`) REFERENCES `contact` (`contact_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contactgroup_contact_relation_ibfk_2` FOREIGN KEY (`contactgroup_cg_id`) REFERENCES `contactgroup` (`cg_id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `contactgroup_hostgroup_relation`
--
ALTER TABLE `contactgroup_hostgroup_relation`
  ADD CONSTRAINT `contactgroup_hostgroup_relation_ibfk_1` FOREIGN KEY (`contactgroup_cg_id`) REFERENCES `contactgroup` (`cg_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contactgroup_hostgroup_relation_ibfk_2` FOREIGN KEY (`hostgroup_hg_id`) REFERENCES `hostgroup` (`hg_id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `contactgroup_host_relation`
--
ALTER TABLE `contactgroup_host_relation`
  ADD CONSTRAINT `contactgroup_host_relation_ibfk_1` FOREIGN KEY (`host_host_id`) REFERENCES `host` (`host_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contactgroup_host_relation_ibfk_2` FOREIGN KEY (`contactgroup_cg_id`) REFERENCES `contactgroup` (`cg_id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `contactgroup_servicegroup_relation`
--
ALTER TABLE `contactgroup_servicegroup_relation`
  ADD CONSTRAINT `contactgroup_servicegroup_relation_ibfk_1` FOREIGN KEY (`contactgroup_cg_id`) REFERENCES `contactgroup` (`cg_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contactgroup_servicegroup_relation_ibfk_2` FOREIGN KEY (`servicegroup_sg_id`) REFERENCES `servicegroup` (`sg_id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `contactgroup_service_relation`
--
ALTER TABLE `contactgroup_service_relation`
  ADD CONSTRAINT `contactgroup_service_relation_ibfk_1` FOREIGN KEY (`contactgroup_cg_id`) REFERENCES `contactgroup` (`cg_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contactgroup_service_relation_ibfk_2` FOREIGN KEY (`service_service_id`) REFERENCES `service` (`service_id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `contact_hostcommands_relation`
--
ALTER TABLE `contact_hostcommands_relation`
  ADD CONSTRAINT `contact_hostcommands_relation_ibfk_1` FOREIGN KEY (`contact_contact_id`) REFERENCES `contact` (`contact_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contact_hostcommands_relation_ibfk_2` FOREIGN KEY (`command_command_id`) REFERENCES `command` (`command_id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `contact_param`
--
ALTER TABLE `contact_param`
  ADD CONSTRAINT `contact_param_ibfk_1` FOREIGN KEY (`cp_contact_id`) REFERENCES `contact` (`contact_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Contraintes pour la table `contact_servicecommands_relation`
--
ALTER TABLE `contact_servicecommands_relation`
  ADD CONSTRAINT `contact_servicecommands_relation_ibfk_1` FOREIGN KEY (`contact_contact_id`) REFERENCES `contact` (`contact_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contact_servicecommands_relation_ibfk_2` FOREIGN KEY (`command_command_id`) REFERENCES `command` (`command_id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `dependency_hostChild_relation`
--
ALTER TABLE `dependency_hostChild_relation`
  ADD CONSTRAINT `dependency_hostChild_relation_ibfk_1` FOREIGN KEY (`dependency_dep_id`) REFERENCES `dependency` (`dep_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `dependency_hostChild_relation_ibfk_2` FOREIGN KEY (`host_host_id`) REFERENCES `host` (`host_id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `dependency_hostgroupChild_relation`
--
ALTER TABLE `dependency_hostgroupChild_relation`
  ADD CONSTRAINT `dependency_hostgroupChild_relation_ibfk_1` FOREIGN KEY (`dependency_dep_id`) REFERENCES `dependency` (`dep_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `dependency_hostgroupChild_relation_ibfk_2` FOREIGN KEY (`hostgroup_hg_id`) REFERENCES `hostgroup` (`hg_id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `dependency_hostgroupParent_relation`
--
ALTER TABLE `dependency_hostgroupParent_relation`
  ADD CONSTRAINT `dependency_hostgroupParent_relation_ibfk_1` FOREIGN KEY (`dependency_dep_id`) REFERENCES `dependency` (`dep_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `dependency_hostgroupParent_relation_ibfk_2` FOREIGN KEY (`hostgroup_hg_id`) REFERENCES `hostgroup` (`hg_id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `dependency_hostParent_relation`
--
ALTER TABLE `dependency_hostParent_relation`
  ADD CONSTRAINT `dependency_hostParent_relation_ibfk_1` FOREIGN KEY (`dependency_dep_id`) REFERENCES `dependency` (`dep_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `dependency_hostParent_relation_ibfk_2` FOREIGN KEY (`host_host_id`) REFERENCES `host` (`host_id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `dependency_metaserviceChild_relation`
--
ALTER TABLE `dependency_metaserviceChild_relation`
  ADD CONSTRAINT `dependency_metaserviceChild_relation_ibfk_1` FOREIGN KEY (`dependency_dep_id`) REFERENCES `dependency` (`dep_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `dependency_metaserviceChild_relation_ibfk_2` FOREIGN KEY (`meta_service_meta_id`) REFERENCES `meta_service` (`meta_id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `dependency_metaserviceParent_relation`
--
ALTER TABLE `dependency_metaserviceParent_relation`
  ADD CONSTRAINT `dependency_metaserviceParent_relation_ibfk_1` FOREIGN KEY (`dependency_dep_id`) REFERENCES `dependency` (`dep_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `dependency_metaserviceParent_relation_ibfk_2` FOREIGN KEY (`meta_service_meta_id`) REFERENCES `meta_service` (`meta_id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `dependency_serviceChild_relation`
--
ALTER TABLE `dependency_serviceChild_relation`
  ADD CONSTRAINT `dependency_serviceChild_relation_ibfk_1` FOREIGN KEY (`dependency_dep_id`) REFERENCES `dependency` (`dep_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `dependency_serviceChild_relation_ibfk_2` FOREIGN KEY (`service_service_id`) REFERENCES `service` (`service_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `dependency_serviceChild_relation_ibfk_3` FOREIGN KEY (`host_host_id`) REFERENCES `host` (`host_id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `dependency_servicegroupChild_relation`
--
ALTER TABLE `dependency_servicegroupChild_relation`
  ADD CONSTRAINT `dependency_servicegroupChild_relation_ibfk_1` FOREIGN KEY (`dependency_dep_id`) REFERENCES `dependency` (`dep_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `dependency_servicegroupChild_relation_ibfk_2` FOREIGN KEY (`servicegroup_sg_id`) REFERENCES `servicegroup` (`sg_id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `dependency_servicegroupParent_relation`
--
ALTER TABLE `dependency_servicegroupParent_relation`
  ADD CONSTRAINT `dependency_servicegroupParent_relation_ibfk_1` FOREIGN KEY (`dependency_dep_id`) REFERENCES `dependency` (`dep_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `dependency_servicegroupParent_relation_ibfk_2` FOREIGN KEY (`servicegroup_sg_id`) REFERENCES `servicegroup` (`sg_id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `dependency_serviceParent_relation`
--
ALTER TABLE `dependency_serviceParent_relation`
  ADD CONSTRAINT `dependency_serviceParent_relation_ibfk_1` FOREIGN KEY (`dependency_dep_id`) REFERENCES `dependency` (`dep_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `dependency_serviceParent_relation_ibfk_2` FOREIGN KEY (`service_service_id`) REFERENCES `service` (`service_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `dependency_serviceParent_relation_ibfk_3` FOREIGN KEY (`host_host_id`) REFERENCES `host` (`host_id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `escalation`
--
ALTER TABLE `escalation`
  ADD CONSTRAINT `escalation_ibfk_1` FOREIGN KEY (`escalation_period`) REFERENCES `timeperiod` (`tp_id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `escalation_contactgroup_relation`
--
ALTER TABLE `escalation_contactgroup_relation`
  ADD CONSTRAINT `escalation_contactgroup_relation_ibfk_1` FOREIGN KEY (`escalation_esc_id`) REFERENCES `escalation` (`esc_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `escalation_contactgroup_relation_ibfk_2` FOREIGN KEY (`contactgroup_cg_id`) REFERENCES `contactgroup` (`cg_id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `escalation_hostgroup_relation`
--
ALTER TABLE `escalation_hostgroup_relation`
  ADD CONSTRAINT `escalation_hostgroup_relation_ibfk_1` FOREIGN KEY (`escalation_esc_id`) REFERENCES `escalation` (`esc_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `escalation_hostgroup_relation_ibfk_2` FOREIGN KEY (`hostgroup_hg_id`) REFERENCES `hostgroup` (`hg_id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `escalation_host_relation`
--
ALTER TABLE `escalation_host_relation`
  ADD CONSTRAINT `escalation_host_relation_ibfk_1` FOREIGN KEY (`escalation_esc_id`) REFERENCES `escalation` (`esc_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `escalation_host_relation_ibfk_2` FOREIGN KEY (`host_host_id`) REFERENCES `host` (`host_id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `escalation_meta_service_relation`
--
ALTER TABLE `escalation_meta_service_relation`
  ADD CONSTRAINT `escalation_meta_service_relation_ibfk_1` FOREIGN KEY (`escalation_esc_id`) REFERENCES `escalation` (`esc_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `escalation_meta_service_relation_ibfk_2` FOREIGN KEY (`meta_service_meta_id`) REFERENCES `meta_service` (`meta_id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `escalation_servicegroup_relation`
--
ALTER TABLE `escalation_servicegroup_relation`
  ADD CONSTRAINT `escalation_servicegroup_relation_ibfk_1` FOREIGN KEY (`escalation_esc_id`) REFERENCES `escalation` (`esc_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `escalation_servicegroup_relation_ibfk_2` FOREIGN KEY (`servicegroup_sg_id`) REFERENCES `servicegroup` (`sg_id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `escalation_service_relation`
--
ALTER TABLE `escalation_service_relation`
  ADD CONSTRAINT `escalation_service_relation_ibfk_1` FOREIGN KEY (`escalation_esc_id`) REFERENCES `escalation` (`esc_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `escalation_service_relation_ibfk_2` FOREIGN KEY (`service_service_id`) REFERENCES `service` (`service_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `escalation_service_relation_ibfk_3` FOREIGN KEY (`host_host_id`) REFERENCES `host` (`host_id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `extended_host_information`
--
ALTER TABLE `extended_host_information`
  ADD CONSTRAINT `extended_host_information_ibfk_1` FOREIGN KEY (`host_host_id`) REFERENCES `host` (`host_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `extended_host_information_ibfk_2` FOREIGN KEY (`ehi_icon_image`) REFERENCES `view_img` (`img_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `extended_host_information_ibfk_3` FOREIGN KEY (`ehi_vrml_image`) REFERENCES `view_img` (`img_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `extended_host_information_ibfk_4` FOREIGN KEY (`ehi_statusmap_image`) REFERENCES `view_img` (`img_id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `extended_service_information`
--
ALTER TABLE `extended_service_information`
  ADD CONSTRAINT `extended_service_information_ibfk_1` FOREIGN KEY (`graph_id`) REFERENCES `giv_graphs_template` (`graph_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `extended_service_information_ibfk_2` FOREIGN KEY (`service_service_id`) REFERENCES `service` (`service_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `extended_service_information_ibfk_3` FOREIGN KEY (`esi_icon_image`) REFERENCES `view_img` (`img_id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `host`
--
ALTER TABLE `host`
  ADD CONSTRAINT `host_ibfk_1` FOREIGN KEY (`command_command_id`) REFERENCES `command` (`command_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `host_ibfk_2` FOREIGN KEY (`command_command_id2`) REFERENCES `command` (`command_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `host_ibfk_3` FOREIGN KEY (`timeperiod_tp_id`) REFERENCES `timeperiod` (`tp_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `host_ibfk_4` FOREIGN KEY (`timeperiod_tp_id2`) REFERENCES `timeperiod` (`tp_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `host_ibfk_5` FOREIGN KEY (`purge_policy_id`) REFERENCES `purge_policy` (`purge_policy_id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `hostgroup_relation`
--
ALTER TABLE `hostgroup_relation`
  ADD CONSTRAINT `hostgroup_relation_ibfk_1` FOREIGN KEY (`hostgroup_hg_id`) REFERENCES `hostgroup` (`hg_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `hostgroup_relation_ibfk_2` FOREIGN KEY (`host_host_id`) REFERENCES `host` (`host_id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `host_hostparent_relation`
--
ALTER TABLE `host_hostparent_relation`
  ADD CONSTRAINT `host_hostparent_relation_ibfk_1` FOREIGN KEY (`host_parent_hp_id`) REFERENCES `host` (`host_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `host_hostparent_relation_ibfk_2` FOREIGN KEY (`host_host_id`) REFERENCES `host` (`host_id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `host_service_relation`
--
ALTER TABLE `host_service_relation`
  ADD CONSTRAINT `host_service_relation_ibfk_1` FOREIGN KEY (`hostgroup_hg_id`) REFERENCES `hostgroup` (`hg_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `host_service_relation_ibfk_2` FOREIGN KEY (`host_host_id`) REFERENCES `host` (`host_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `host_service_relation_ibfk_3` FOREIGN KEY (`servicegroup_sg_id`) REFERENCES `servicegroup` (`sg_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `host_service_relation_ibfk_4` FOREIGN KEY (`service_service_id`) REFERENCES `service` (`service_id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `meta_contactgroup_relation`
--
ALTER TABLE `meta_contactgroup_relation`
  ADD CONSTRAINT `meta_contactgroup_relation_ibfk_1` FOREIGN KEY (`meta_id`) REFERENCES `meta_service` (`meta_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `meta_contactgroup_relation_ibfk_2` FOREIGN KEY (`cg_cg_id`) REFERENCES `contactgroup` (`cg_id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `meta_service`
--
ALTER TABLE `meta_service`
  ADD CONSTRAINT `meta_service_ibfk_1` FOREIGN KEY (`check_period`) REFERENCES `timeperiod` (`tp_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `meta_service_ibfk_2` FOREIGN KEY (`notification_period`) REFERENCES `timeperiod` (`tp_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `meta_service_ibfk_3` FOREIGN KEY (`graph_id`) REFERENCES `giv_graphs_template` (`graph_id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `meta_service_relation`
--
ALTER TABLE `meta_service_relation`
  ADD CONSTRAINT `meta_service_relation_ibfk_1` FOREIGN KEY (`host_id`) REFERENCES `host` (`host_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `meta_service_relation_ibfk_2` FOREIGN KEY (`meta_id`) REFERENCES `meta_service` (`meta_id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `ns_host_relation`
--
ALTER TABLE `ns_host_relation`
  ADD CONSTRAINT `ns_host_relation_ibfk_2` FOREIGN KEY (`nagios_server_id`) REFERENCES `nagios_server` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ns_host_relation_ibfk_3` FOREIGN KEY (`host_host_id`) REFERENCES `host` (`host_id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `reporting_diff_list`
--
ALTER TABLE `reporting_diff_list`
  ADD CONSTRAINT `reporting_diff_list_ibfk_1` FOREIGN KEY (`tp_id`) REFERENCES `timeperiod` (`tp_id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `reporting_email_list_relation`
--
ALTER TABLE `reporting_email_list_relation`
  ADD CONSTRAINT `reporting_email_list_relation_ibfk_1` FOREIGN KEY (`rtdl_id`) REFERENCES `reporting_diff_list` (`rtdl_id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `service`
--
ALTER TABLE `service`
  ADD CONSTRAINT `service_ibfk_1` FOREIGN KEY (`command_command_id`) REFERENCES `command` (`command_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `service_ibfk_2` FOREIGN KEY (`command_command_id2`) REFERENCES `command` (`command_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `service_ibfk_3` FOREIGN KEY (`timeperiod_tp_id`) REFERENCES `timeperiod` (`tp_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `service_ibfk_4` FOREIGN KEY (`timeperiod_tp_id2`) REFERENCES `timeperiod` (`tp_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `service_ibfk_5` FOREIGN KEY (`purge_policy_id`) REFERENCES `purge_policy` (`purge_policy_id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `servicegroup_relation`
--
ALTER TABLE `servicegroup_relation`
  ADD CONSTRAINT `servicegroup_relation_ibfk_10` FOREIGN KEY (`servicegroup_sg_id`) REFERENCES `servicegroup` (`sg_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `servicegroup_relation_ibfk_7` FOREIGN KEY (`host_host_id`) REFERENCES `host` (`host_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `servicegroup_relation_ibfk_8` FOREIGN KEY (`hostgroup_hg_id`) REFERENCES `hostgroup` (`hg_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `servicegroup_relation_ibfk_9` FOREIGN KEY (`service_service_id`) REFERENCES `service` (`service_id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `service_categories_relation`
--
ALTER TABLE `service_categories_relation`
  ADD CONSTRAINT `service_categories_relation_ibfk_1` FOREIGN KEY (`service_service_id`) REFERENCES `service` (`service_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `service_categories_relation_ibfk_2` FOREIGN KEY (`sc_id`) REFERENCES `service_categories` (`sc_id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `topology_JS`
--
ALTER TABLE `topology_JS`
  ADD CONSTRAINT `topology_JS_ibfk_1` FOREIGN KEY (`id_page`) REFERENCES `topology` (`topology_page`) ON DELETE CASCADE;

--
-- Contraintes pour la table `traps`
--
ALTER TABLE `traps`
  ADD CONSTRAINT `traps_ibfk_1` FOREIGN KEY (`manufacturer_id`) REFERENCES `traps_vendor` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `traps_service_relation`
--
ALTER TABLE `traps_service_relation`
  ADD CONSTRAINT `traps_service_relation_ibfk_2` FOREIGN KEY (`service_id`) REFERENCES `service` (`service_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `traps_service_relation_ibfk_3` FOREIGN KEY (`traps_id`) REFERENCES `traps` (`traps_id`) ON DELETE CASCADE;
