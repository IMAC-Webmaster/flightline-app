/* 
 * Bare bones SQL script
 */

drop table round;
CREATE TABLE round
(
   round_id       integer primary key,
   flightline     tinyint,
   imac_class     varchar(15) not null,
   imac_type      varchar(15) CHECK( imac_type IN ('Known','Unknown','Freestyle') ) NOT NULL DEFAULT 'Known',
   roundnum       number tinyint not null,
   comproundnum   number tinyint,
   sched_id       varchar(8) not null,
   sequences      tinyint not null,
   phase          text CHECK( status IN ('U','O','P','D') ) NOT NULL DEFAULT 'U',
   status         text DEFAULT null,
   starttime      integer,
   finishtime     integer,
   UNIQUE (imac_class, imac_type, roundnum) ON CONFLICT ROLLBACK
);

/****************************
   U = Unflown
   O = Open (flying)
   P = Paused (stopped but not done yet).
   D = Done - complete.
*****************************/
drop table user;
CREATE TABLE user
(
   user_id        varchar not null primary key,
   fullname       varchar not null,
   password       varchar not null,
   address        varchar not null
);

drop table schedule;
CREATE TABLE schedule (
   schedule_id    varchar(8) not null primary key,
   imac_class     varchar(15),
   imac_type      varchar(15) CHECK( imac_type IN ('Known','Unknown','Freestyle') ) NOT NULL DEFAULT 'Known',
   description    varchar(64) not null
);

drop table figure;
CREATE TABLE figure (
   figurenum      tinyint not null,
   sched_id       varchar(8),
   short_desc     varchar (20) not null,
   long_desc      varchar(128),
   rule           tinyint not null,
   k              tinyint not null,
   PRIMARY KEY(figurenum, sched)
);

insert into user values ('danny', 'Dan Carroll', '12qwaszx', 'Some Address.');
insert into user values ('nicole', 'Nicole McNaughton', '12qwaszx', 'Some Address.');

insert into schedule values ('BAS-KNWN', 'Basic', 'Known', 'Basic Known 2018');
insert into schedule values ('SPO-KNWN', 'Sportsman', 'Known', 'Sportsman Known 2018');
insert into schedule values ('INT-KNWN', 'Intermediate', 'Known', 'Intermediate Known 2018');
insert into schedule values ('ADV-KNWN', 'Advanced', 'Known', 'Advanced Known 2018');
insert into schedule values ('UNL-KNWN', 'Unlimited', 'Known', 'Unlimited Known 2018');
insert into schedule values ('SPO-ALTK', 'Sportsman', 'Known', 'Sportsman Alternate Known 2018');
insert into schedule values ('INT-ALTK', 'Intermediate', 'Known', 'Intermediate Alternate Known 2018');
insert into schedule values ('ADV-ALTK', 'Advanced', 'Known', 'Advanced Alternate Known 2018');
insert into schedule values ('UNL-ALTK', 'Unlimited', 'Known', 'Unlimited Alternate Known 2018');
insert into schedule values ('BAS-UNKN', 'Basic', 'Unknown', 'Basic Unknown for 2018');
insert into schedule values ('SPO-UNKN', 'Sportsman', 'Unknown', 'Sportsman Unknown for 2018-03-29');
insert into schedule values ('INT-UNKN', 'Intermediate', 'Unknown', 'Intermediate Unknown for 2018-03-29');
insert into schedule values ('ADV-UNKN', 'Advanced', 'Unknown', 'Advanced Unknown for 2018-03-29');
insert into schedule values ('UNL-UNKN', 'Unlimited', 'Unknown', 'Unlimited Unknown for 2018-03-29');
insert into schedule values ('FREE', null, 'Freestyle', 'Freestyle');

insert into figure values (1, 'BAS-KNWN', 'Full Roll', null, 1, 10);
insert into figure values (2, 'BAS-KNWN', 'Hammerhead', null, 1, 17);
insert into figure values (3, 'BAS-KNWN', 'Rev. Half Cuban', 'Reverse Half Cuban', 1, 16);
insert into figure values (4, 'BAS-KNWN', 'Loop', null, 1, 10);
insert into figure values (5, 'BAS-KNWN', 'LD Humpty', 'Lay Down Humptybump', 1, 16);
insert into figure values (6, 'BAS-KNWN', 'Rev. Teardrop', 'Reverse Teardrop', 1, 16);
insert into figure values (7, 'BAS-KNWN', '360 Aero turn', '360 Degree Aerobatic turn', 1, 6);
insert into figure values (8, 'BAS-KNWN', 'Humpty Bump', null, 1, 17);
insert into figure values (9, 'BAS-KNWN', 'Immelmann', null, 1, 10);
insert into figure values (10, 'BAS-KNWN', '1 1/2 Pos. Spin', '1 and 1/2 turn positive upright spin', 1, 13);
insert into figure values (11, 'BAS-KNWN', 'Sound', null, 2, 3);
insert into figure values (12, 'BAS-KNWN', 'Airspace', 'Airspace Control', 2, 3);


insert into round (imac_class, imac_type, roundnum, sched_id, sequences, phase) values ('Basic', 'Known', 1, 'BAS-KNWN', 2, 'U');
insert into round (imac_class, imac_type, roundnum, sched_id, sequences, phase) values ('Basic', 'Known', 2, 'BAS-KNWN', 2, 'U');
insert into round (imac_class, imac_type, roundnum, sched_id, sequences, phase) values ('Basic', 'Known', 3, 'BAS-KNWN', 2, 'U');
insert into round (imac_class, imac_type, roundnum, sched_id, sequences, phase) values ('Basic', 'Unknown', 1, 'BAS-UNKN', 1, 'U');
insert into round (imac_class, imac_type, roundnum, sched_id, sequences, phase) values ('Sportsman', 'Known', 1, 'SPO-KNWN', 2, 'U');
insert into round (imac_class, imac_type, roundnum, sched_id, sequences, phase) values ('Sportsman', 'Known', 2, 'SPO-KNWN', 2, 'U');
insert into round (imac_class, imac_type, roundnum, sched_id, sequences, phase) values ('Sportsman', 'Known', 3, 'SPO-KNWN', 2, 'U');
insert into round (imac_class, imac_type, roundnum, sched_id, sequences, phase) values ('Sportsman', 'Unknown', 1, 'SPO-UNKN', 1, 'U');
insert into round (imac_class, imac_type, roundnum, sched_id, sequences, phase) values ('Intermediate', 'Known', 1, 'INT-KNWN', 2, 'U');
insert into round (imac_class, imac_type, roundnum, sched_id, sequences, phase) values ('Intermediate', 'Known', 2, 'INT-KNWN', 2, 'U');
insert into round (imac_class, imac_type, roundnum, sched_id, sequences, phase) values ('Intermediate', 'Known', 3, 'INT-KNWN', 2, 'U');
insert into round (imac_class, imac_type, roundnum, sched_id, sequences, phase) values ('Intermediate', 'Unknown', 1, 'INT-UNKN', 1, 'U');
insert into round (imac_class, imac_type, roundnum, sched_id, sequences, phase) values ('Any', 'Freestyle', 1, 'FREE', 1, 'U');

