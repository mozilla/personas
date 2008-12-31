create table if not exists personas
(
 id integer primary key not null auto_increment,
 name varchar(32) unique,
 header varchar(64),
 footer varchar(64),
 category varchar(32),
 status tinyint,
 submit varchar(32),
 approve varchar(32),
 author varchar(32),
 accentcolor varchar(10),
 textcolor varchar(10),
 popularity integer
);

create table if not exists categories
(
 id integer primary key not null auto_increment,
 name varchar(32)
);

create table if not exists users
(
 username varchar(32) primary key,
 md5 varchar(32),
 email varchar(64),
 admin tinyint default 0
);

create table if not exists edits
(
 id integer primary key not null,
 author varchar(32),
 name varchar(32) unique,
 header varchar(64),
 footer varchar(64),
 category varchar(32),
 accentcolor varchar(10),
 textcolor varchar(10)
);


create table if not exists log
(
 id integer,
 username varchar(32),
 action text,
 date timestamp
);

