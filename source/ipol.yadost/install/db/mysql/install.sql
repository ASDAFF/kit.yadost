create table if not exists ipol_yadost
(
	ID int(11) NOT NULL auto_increment,
	PARAMS text,
	ORDER_ID int(11),
	delivery_ID int(12),
	parcel_ID int(12),
	STATUS varchar(40),
	MESSAGE text,
	UPTIME varchar(10),
	PRIMARY KEY(ID),
	INDEX ix_ipol_yadost_oi (ORDER_ID)
);