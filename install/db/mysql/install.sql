CREATE TABLE IF NOT EXISTS intervolga_migrato_data(
	ID INT(11) NOT NULL AUTO_INCREMENT,
	MODULE_NAME VARCHAR(200),
	ENTITY_NAME VARCHAR(200),
	DATA_XML_ID VARCHAR(200) NOT NULL,

	DATA_ID_NUM INT(11),
	DATA_ID_STR VARCHAR(200),
	DATA_ID_COMPLEX VARCHAR(500),
	PRIMARY KEY (ID)
);