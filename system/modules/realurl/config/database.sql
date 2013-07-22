-- **********************************************************
-- *                                                        *
-- * IMPORTANT NOTE                                         *
-- *                                                        *
-- * Do not import this file manually but use the TYPOlight *
-- * install tool to create and maintain database tables!   *
-- *                                                        *
-- **********************************************************

-- 
-- Table `tl_page`
-- 

CREATE TABLE `tl_page` (
  `folderAlias` char(1) NOT NULL default '',
  `subAlias` char(1) NOT NULL default '',
  `realurl_no_inheritance` char(1) NOT NULL default '',
  `realurl_overwrite` char(1) NOT NULL default '',
  `useRootAlias` char(1) NOT NULL default '',
  `realurl_basealias` text NULL,
  `realurl_force_alias` char(1) NOT NULL default '',
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- 
-- Table `tl_realurl_aliases`
--

CREATE TABLE `tl_realurl_aliases` (
  `id` int(10) unsigned NOT NULL auto_increment,    
  `alias` char(255) NOT NULL default '',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

