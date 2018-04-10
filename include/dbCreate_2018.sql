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
   next_pilot_id  integer,
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


drop table user;
CREATE TABLE user
(
   user_id        varchar not null primary key,
   fullname       varchar not null,
   password       varchar not null,
   address        varchar not null
);

insert into user values ('danny', 'Dan Carroll', '12qwaszx', 'Some Address.');
insert into user values ('nicole', 'Nicole McNaughton', '12qwaszx', 'Some Address.');


drop table schedule;
CREATE TABLE schedule (
   schedule_id    varchar(8) not null primary key,
   imac_class     varchar(15),
   imac_type      varchar(15) CHECK( imac_type IN ('Known','Unknown','Freestyle') ) NOT NULL DEFAULT 'Known',
   description    varchar(64) not null
);

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
insert into schedule values ('SPO-UNKN', 'Sportsman', 'Unknown', 'Sportsman Unknown Example');
insert into schedule values ('INT-UNKN', 'Intermediate', 'Unknown', 'Intermediate Unknown Example');
insert into schedule values ('ADV-UNKN', 'Advanced', 'Unknown', 'Advanced Unknown Example');
insert into schedule values ('UNL-UNKN', 'Unlimited', 'Unknown', 'Unlimited Unknown Example');
insert into schedule values ('FREE', null, 'Freestyle', 'Freestyle');

drop table figure;
CREATE TABLE figure (
   figurenum      tinyint not null,
   sched_id       varchar(8),
   short_desc     varchar (20) not null,
   long_desc      varchar(128),
   rule           tinyint not null,
   k              tinyint not null,
   PRIMARY KEY(figurenum, sched_id)
);

insert into figure values (1, 'BAS-KNWN', 'Full Roll', 'Full Roll', 1, 10);
insert into figure values (2, 'BAS-KNWN', 'Hammerhead', 'HHammerhead turn', 1, 17);
insert into figure values (3, 'BAS-KNWN', 'Rev. Half Cuban', 'Reverse Half Cuban', 1, 16);
insert into figure values (4, 'BAS-KNWN', 'Loop', 'Loop', 1, 10);
insert into figure values (5, 'BAS-KNWN', 'LD Humpty', 'Lay Down Humptybump', 1, 16);
insert into figure values (6, 'BAS-KNWN', 'Rev. Teardrop', 'Reverse Teardrop', 1, 16);
insert into figure values (7, 'BAS-KNWN', '360 Aero turn', '360 Degree Aerobatic turn', 1, 6);
insert into figure values (8, 'BAS-KNWN', 'Humpty Bump', 'Humpty Bump', 1, 17);
insert into figure values (9, 'BAS-KNWN', 'Immelmann', 'Immelmann turn', 1, 10);
insert into figure values (10, 'BAS-KNWN', '1 1/2 Pos. Spin', '1 and 1/2 turn positive upright spin', 1, 13);
insert into figure values (11, 'BAS-KNWN', 'Sound', 'Sound', 2, 3);
insert into figure values (12, 'BAS-KNWN', 'Airspace', 'Airspace Control', 2, 3);


drop table pilot;
create table pilot (
   pilot_id         integer not null primary key,
   primary_id       integer not null,
   secondary_id     integer,
   fullname         varchar(60) not null,
   airplane         varchar(60),
   freestyle        tinyint CHECK( freestyle IN (0, 1) ) NOT NULL DEFAULT 0,
   imac_class       varchar(15) not null,
   in_customclass1  tinyint CHECK( in_customclass1 IN (0, 1) ) NOT NULL DEFAULT 0,
   in_customclass2  tinyint CHECK( in_customclass2 IN (0, 1) ) NOT NULL DEFAULT 0,
   active           active CHECK( in_customclass2 IN (0, 1) ) NOT NULL DEFAULT 1
);

insert into pilot values (1, 1001, null, 'Dan Carroll', 'Extra 300', 0, 'Intermediate', 0, 0, 1);
insert into pilot values (2, 1002, null, 'Michael Hobson', 'Extra 330', 0, 'Intermediate', 0, 0, 1);


drop table flight;
create table flight (
   flight_id        integer not null primary key,
   pilot_id         integer not null,
   round_id         integer not null
);

insert into flight values (1, 1, 9);
insert into flight values (2, 2, 9);
insert into flight values (3, 1, 10);
insert into flight values (4, 2, 10);
insert into flight values (5, 1, 12);
insert into flight values (6, 2, 12);

drop table sheet;
create table sheet (
   sheet_id         integer not null primary key,
   flight_id        integer not null,
   sequence_num     tinyint not null,
   judge_num        tinyint not null,
   judge_name       text,
   scribe_name      text,
   comment          text,
   mpp_penalty      tinyint CHECK( mpp_penalty IN (0, 1) ) NOT NULL DEFAULT 0,
   flight_zeroed    tinyint CHECK( flight_zeroed IN (0, 1) ) NOT NULL DEFAULT 0,
   zero_reason      text,
   UNIQUE (flight_id, sequence_num, judge_num) ON CONFLICT ROLLBACK
);

insert into sheet values (1, 1, 1, 1, "Judge 1, F1 S1", "Scribe 1 F1, S1", "comment", 0, 0, "zero comment");
insert into sheet values (2, 1, 1, 2, "Judge 2, F1 S1", "Scribe 2 F1, S1", "comment", 0, 0, "zero comment");
insert into sheet values (3, 1, 2, 1, "Judge 1, F1 S2", "Scribe 1 F1, S2", "comment", 0, 0, "zero comment");
insert into sheet values (4, 1, 2, 2, "Judge 2, F1 S2", "Scribe 2 F1, S2", "comment", 0, 0, "zero comment");
insert into sheet values (5, 2, 1, 1, "Judge 1, F2 S1", "Scribe 1 F2, S1", null, 0, 0, null);
insert into sheet values (6, 2, 1, 2, "Judge 2, F2 S1", "Scribe 2 F2, S1", null, 0, 0, null);
insert into sheet values (7, 2, 2, 1, "Judge 1, F2 S2", "Scribe 1 F2, S2", null, 0, 0, null);
insert into sheet values (8, 2, 2, 2, "Judge 2, F2 S2", "Scribe 2 F2, S2", null, 0, 0, null);
insert into sheet values (9, 3, 1, 1, "Judge 1, F3 S1", "Scribe 1 F3, S1", null, 0, 0, null);
insert into sheet values (10, 3, 1, 2, "Judge 2, F3 S1", "Scribe 2 F3, S1", null, 0, 0, null);
insert into sheet values (11, 3, 2, 1, "Judge 1, F3 S2", "Scribe 1 F3, S2", null, 0, 0, null);
insert into sheet values (12, 3, 2, 2, "Judge 2, F3 S2", "Scribe 2 F3, S2", null, 0, 0, null);
insert into sheet values (13, 4, 1, 1, "Judge 1, F4 S1", "Scribe 1 F4, S1", null, 0, 0, null);
insert into sheet values (14, 4, 1, 2, "Judge 2, F4 S1", "Scribe 2 F4, S1", null, 0, 0, null);
insert into sheet values (15, 4, 2, 1, "Judge 1, F4 S2", "Scribe 1 F4, S2", null, 0, 0, null);
insert into sheet values (16, 4, 2, 2, "Judge 2, F4 S2", "Scribe 2 F4, S2", null, 0, 0, null);
insert into sheet values (17, 5, 1, 1, "Judge 1, F5 S1 unknown", "Scribe 1 F5, S1", null, 0, 0, null);
insert into sheet values (18, 5, 1, 2, "Judge 2, F5 S1 unknown", "Scribe 2 F5, S1", null, 0, 0, null);
insert into sheet values (19, 6, 1, 1, "Judge 1, F6 S1 unknown", "Scribe 1 F6, S1", null, 0, 0, null);
insert into sheet values (20, 6, 1, 2, "Judge 2, F6 S1 unknown", "Scribe 2 F6, S1", null, 0, 0, null);

drop table score;
create table score (
   sheet_id         integer not null,
   figure_num       tinyint not null,
   scoretime        integer not null,
   break_penalty    tinyint CHECK( break_penalty IN (0, 1) ) NOT NULL DEFAULT 0,
   score            numeric,
   comment          text,
   PRIMARY KEY(sheet_id, figure_num)
);

insert into score values (1, 1,  1234567, 0, 4.5, null);
insert into score values (1, 2,  1234567, 0, 4, null);
insert into score values (1, 3,  1234567, 0, 5, null);
insert into score values (1, 4,  1234567, 0, 3, null);
insert into score values (1, 5,  1234567, 0, 5.5, null);
insert into score values (1, 6,  1234567, 0, 8, null);
insert into score values (1, 7,  1234567, 0, 0, "Deadzone");
insert into score values (1, 8,  1234567, 0, 6, null);
insert into score values (1, 9,  1234567, 0, 2, null);
insert into score values (1, 10, 1234567, 0, 5, null);
insert into score values (1, 11, 1234567, 0, 8, null);
insert into score values (1, 12, 1234567, 0, 7, null);

insert into score values (2, 1,  1234567, 0, 5, null);
insert into score values (2, 2,  1234567, 0, 5, null);
insert into score values (2, 3,  1234567, 0, 5, null);
insert into score values (2, 4,  1234567, 0, 3.5, null);
insert into score values (2, 5,  1234567, 0, 4, null);
insert into score values (2, 6,  1234567, 0, 6, null);
insert into score values (2, 7,  1234567, 0, 0, "Deadzone");
insert into score values (2, 8,  1234567, 0, 7, null);
insert into score values (2, 9,  1234567, 0, 2.5, null);
insert into score values (2, 10, 1234567, 0, 5, null);
insert into score values (2, 11, 1234567, 0, 8, null);
insert into score values (2, 12, 1234567, 0, 8, null);

insert into score values (3, 1,  1234567, 0, 6, null);
insert into score values (3, 2,  1234567, 0, 6, null);
insert into score values (3, 3,  1234567, 0, 4, null);
insert into score values (3, 4,  1234567, 0, 8, null);
insert into score values (3, 5,  1234567, 0, 4, null);
insert into score values (3, 6,  1234567, 0, 5.5, null);
insert into score values (3, 7,  1234567, 0, 6, null);
insert into score values (3, 8,  1234567, 0, 3, null);
insert into score values (3, 9,  1234567, 1, 0, null);
insert into score values (3, 10, 1234567, 0, 0, null);
insert into score values (3, 11, 1234567, 0, 8, null);
insert into score values (3, 12, 1234567, 0, 8, null);

insert into score values (4, 1,  1234567, 0, 6, null);
insert into score values (4, 2,  1234567, 0, 7, null);
insert into score values (4, 3,  1234567, 0, 4.5, null);
insert into score values (4, 4,  1234567, 0, 7, null);
insert into score values (4, 5,  1234567, 0, 8, null);
insert into score values (4, 6,  1234567, 0, 6, null);
insert into score values (4, 7,  1234567, 0, 5, null);
insert into score values (4, 8,  1234567, 0, 3, null);
insert into score values (4, 9,  1234567, 1, 0, null);
insert into score values (4, 10, 1234567, 0, 8, null);
insert into score values (4, 11, 1234567, 0, 7, null);
insert into score values (4, 12, 1234567, 0, 7, null);


