/* 
 * Bare bones SQL script
 */


drop table config;
CREATE TABLE config
(
   flightLineId   integer primary key
);

insert into config (flightLineId) values (1);

drop table round;
CREATE TABLE round
(
   roundId        integer primary key,
   flightLine     tinyint,
   imacClass      varchar(15) not null,
   imacType       varchar(15) CHECK( imacType IN ('Known','Unknown','Freestyle') ) NOT NULL DEFAULT 'Known',
   roundNum       number tinyint not null,
   compRoundNum   number tinyint,
   schedId        varchar(8) not null,
   sequences      tinyint not null,
   phase          text CHECK( phase IN ('U','O','P','D') ) NOT NULL DEFAULT 'U',
   status         text DEFAULT null,
   nextPilotId    integer,
   startTime      integer,
   finishTime     integer,
   UNIQUE (imacClass, imacType, roundNum) ON CONFLICT ROLLBACK
);

/****************************
   U = Unflown
   O = Open (flying)
   P = Paused (stopped but not done yet).
   D = Done - complete.
*****************************/

insert into round (imacClass, imacType, roundNum, schedId, sequences, phase) values ('Basic', 'Known', 1, 'BAS-KNWN', 2, 'U');
insert into round (imacClass, imacType, roundNum, schedId, sequences, phase) values ('Basic', 'Known', 2, 'BAS-KNWN', 2, 'U');
insert into round (imacClass, imacType, roundNum, schedId, sequences, phase) values ('Basic', 'Known', 3, 'BAS-KNWN', 2, 'U');
insert into round (imacClass, imacType, roundNum, schedId, sequences, phase) values ('Basic', 'Unknown', 1, 'BAS-UNKN', 1, 'U');
insert into round (imacClass, imacType, roundNum, schedId, sequences, phase) values ('Sportsman', 'Known', 1, 'SPO-KNWN', 2, 'U');
insert into round (imacClass, imacType, roundNum, schedId, sequences, phase) values ('Sportsman', 'Known', 2, 'SPO-KNWN', 2, 'U');
insert into round (imacClass, imacType, roundNum, schedId, sequences, phase) values ('Sportsman', 'Known', 3, 'SPO-KNWN', 2, 'U');
insert into round (imacClass, imacType, roundNum, schedId, sequences, phase) values ('Sportsman', 'Unknown', 1, 'SPO-UNKN', 1, 'U');
insert into round (imacClass, imacType, roundNum, schedId, sequences, phase) values ('Intermediate', 'Known', 1, 'INT-KNWN', 2, 'U');
insert into round (imacClass, imacType, roundNum, schedId, sequences, phase) values ('Intermediate', 'Known', 2, 'INT-KNWN', 2, 'U');
insert into round (imacClass, imacType, roundNum, schedId, sequences, phase) values ('Intermediate', 'Known', 3, 'INT-KNWN', 2, 'U');
insert into round (imacClass, imacType, roundNum, schedId, sequences, phase) values ('Intermediate', 'Unknown', 1, 'INT-UNKN', 1, 'U');
insert into round (imacClass, imacType, roundNum, schedId, sequences, phase) values ('Any', 'Freestyle', 1, 'FREE', 1, 'U');


drop table user;
CREATE TABLE user
(
   username         varchar not null primary key,
   fullName       varchar not null,
   password       varchar not null,
   address        varchar not null
);

insert into user values ('danny', 'Dan Carroll', '12qwaszx', 'Some Address.');
insert into user values ('nicole', 'Nicole McNaughton', '12qwaszx', 'Some Address.');


drop table schedule;
CREATE TABLE schedule (
   schedId        varchar(8) not null primary key,
   imacClass      varchar(15),
   imacType       varchar(15) CHECK( imacType IN ('Known','Unknown','Freestyle') ) NOT NULL DEFAULT 'Known',
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
   figureNum      tinyint not null,
   schedId        varchar(8),
   shortDesc      varchar (20) not null,
   longDesc       varchar(128),
   rule           tinyint not null,
   k              tinyint not null,
   PRIMARY KEY(figureNum, schedId)
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
   pilotId         integer not null primary key,
   primary_id       integer not null,
   secondary_id     integer,
   fullName         varchar(60) not null,
   airplane         varchar(60),
   freestyle        tinyint CHECK( freestyle IN (0, 1) ) NOT NULL DEFAULT 0,
   imacClass       varchar(15) not null,
   in_customclass1  tinyint CHECK( in_customclass1 IN (0, 1) ) NOT NULL DEFAULT 0,
   in_customclass2  tinyint CHECK( in_customclass2 IN (0, 1) ) NOT NULL DEFAULT 0,
   active           active CHECK( in_customclass2 IN (0, 1) ) NOT NULL DEFAULT 1
);

insert into pilot values (1, 1001, null, 'Dan Carroll', 'Extra 300', 0, 'Intermediate', 0, 0, 1);
insert into pilot values (2, 1002, null, 'Michael Hobson', 'Extra 330', 0, 'Intermediate', 0, 0, 1);


drop table flight;
create table flight (
   flightId         integer not null primary key,
   pilotId          integer not null,
   roundId          integer not null
);

insert into flight values (1, 1, 9);
insert into flight values (2, 2, 9);
insert into flight values (3, 1, 10);
insert into flight values (4, 2, 10);
insert into flight values (5, 1, 12);
insert into flight values (6, 2, 12);

drop table sheet;
create table sheet (
   sheetId          integer not null primary key,
   flightId         integer not null,
   sequenceNum      tinyint not null,
   judgeNum         tinyint not null,
   judgeName        text,
   scribeName       text,
   comment          text,
   mppPenalty       tinyint CHECK( mppPenalty IN (0, 1) ) NOT NULL DEFAULT 0,
   flightZeroed     tinyint CHECK( flightZeroed IN (0, 1) ) NOT NULL DEFAULT 0,
   zeroReason       text,
   UNIQUE (flightId, sequenceNum, judgeNum) ON CONFLICT ROLLBACK
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
   sheetId          integer not null,
   figureNum        tinyint not null,
   scoreTime        integer not null,
   breakPenalty     tinyint CHECK( breakPenalty IN (0, 1) ) NOT NULL DEFAULT 0,
   score            numeric,
   comment          text,
   PRIMARY KEY(sheetId, figureNum)
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


