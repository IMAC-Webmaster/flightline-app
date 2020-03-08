/* 
 * Bare bones SQL script
 */


drop table state;
CREATE TABLE IF NOT EXISTS "state" (
   `key`	TEXT NOT NULL,
   `value`	TEXT,
   PRIMARY KEY(`key`)
);

drop table round;
CREATE TABLE IF NOT EXISTS "round" (
     `roundId`	integer,
     `flightLine`	tinyint,
     `imacClass`	varchar ( 15 ) NOT NULL,
     `imacType`	varchar ( 15 ) NOT NULL DEFAULT 'Known' CHECK(imacType IN ( 'Known' , 'Unknown' , 'Freestyle' )),
     `roundNum`	number tinyint NOT NULL,
     `compRoundNum`	number tinyint,
     `schedId`	varchar ( 8 ) NOT NULL,
     `sequences`	tinyint NOT NULL,
     `phase`	text NOT NULL DEFAULT 'U' CHECK(phase IN ( 'U' , 'O' , 'P' , 'D' )),
     `status`	text DEFAULT null,
     `startTime`	integer,
     `finishTime`	integer,
     PRIMARY KEY(`roundId`),
     FOREIGN KEY(`schedId`) REFERENCES `schedule`(`schedId`),
     UNIQUE(`imacClass`,`imacType`,`roundNum`)
);

/****************************
Phases:
   U = Unflown
   O = Open (flying)
   P = Paused (stopped but not done yet).
   D = Done - complete.
*****************************/

drop table user;
CREATE TABLE "user"
(
   username varchar not null,
   fullName varchar not null,
   password varchar not null,
   address varchar not null,
   roles varchar not null,
   PRIMARY KEY(`username`)
);

drop table schedule;
CREATE TABLE schedule (
   schedId        varchar(8) not null primary key,
   imacClass      varchar(15),
   imacType       varchar(15) CHECK( imacType IN ('Known','Unknown','Freestyle') ) NOT NULL DEFAULT 'Known',
   description    varchar(64) not null
);

drop table figure;
CREATE TABLE "figure" (
   `figureNum`	tinyint NOT NULL,
   `schedId`	varchar ( 8 ),
   `shortDesc`	varchar ( 20 ) NOT NULL,
   `longDesc`	varchar ( 128 ),
   `spokenText`	TEXT,
   `rule`	tinyint NOT NULL DEFAULT 1,
   `k`	tinyint NOT NULL,
   PRIMARY KEY(`figureNum`,`schedId`)
);

drop table pilot;
CREATE TABLE "pilot" (
   `pilotId`	integer NOT NULL,
   `primaryId`	integer NOT NULL,
   `secondaryId`	integer,
   `fullName`	varchar ( 60 ) NOT NULL,
   `airplane`	varchar ( 60 ),
   `freestyle`	tinyint NOT NULL DEFAULT 0 CHECK(freestyle IN ( 0 , 1 )),
   `imacClass`	varchar ( 15 ) NOT NULL,
   `in_customclass1`	tinyint NOT NULL DEFAULT 0 CHECK(in_customclass1 IN ( 0 , 1 )),
   `in_customclass2`	tinyint NOT NULL DEFAULT 0 CHECK(in_customclass2 IN ( 0 , 1 )),
   `active`	active NOT NULL DEFAULT 1 CHECK(active IN ( 0 , 1 )),
   PRIMARY KEY(`pilotId`)
);

drop table flight;
CREATE TABLE `flight` (
   `flightId`	integer NOT NULL PRIMARY KEY AUTOINCREMENT,
   `noteFlightId`	integer NOT NULL,
   `roundId`	integer NOT NULL,
   `sequenceNum`	integer NOT NULL,
   CONSTRAINT `flightid_round` UNIQUE(`noteFlightId`,`roundId`),
   CONSTRAINT `roundid_seq` UNIQUE(`roundId`,`sequenceNum`)
);

drop table flightOrder;
CREATE TABLE `flightOrder` (
   `roundId`      integer NOT NULL,
   `pilotId`      integer NOT NULL,
   `noteFlightId` integer NOT NULL,
   `flightOrder`  integer NOT NULL,
   PRIMARY KEY(`roundId`,`pilotId`, `noteFlightId`),
   FOREIGN KEY(`roundId`) REFERENCES `round`(`roundId`) ON DELETE CASCADE ON UPDATE CASCADE,
   FOREIGN KEY(`pilotId`) REFERENCES `pilot`(`pilotId`) ON DELETE CASCADE ON UPDATE CASCADE
);

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

drop table score;
CREATE TABLE `score` (
   `sheetId`    integer NOT NULL,
   `figureNum`    tinyint NOT NULL,
   `scoreTime`    integer NOT NULL,
   `breakFlag`    tinyint NOT NULL DEFAULT 0 CHECK(breakFlag IN ( 0 , 1 )),
   `flags`    integer NOT NULL DEFAULT 0,
   `score`    numeric,
   `comment`    text,
   PRIMARY KEY(`sheetId`,`figureNum`),
   FOREIGN KEY(`sheetId`) REFERENCES `sheet`(`sheetId`) ON DELETE CASCADE ON UPDATE CASCADE
);

drop table sheet;
CREATE TABLE "sheet" (
   `sheetId`        INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
   `roundId`        integer NOT NULL,
   `flightId`       integer NOT NULL,
   `pilotId`        integer NOT NULL,
   `judgeNum`       tinyint NOT NULL,
   `judgeName`      text DEFAULT null,
   `scribeName`     text DEFAULT null,
   `comment`        text DEFAULT null,
   `mppFlag`        tinyint NOT NULL DEFAULT 0 CHECK(mppFlag IN ( 0 , 1 )),
   `flightZeroed`   tinyint NOT NULL DEFAULT 0 CHECK(flightZeroed IN ( 0 , 1 )),
   `zeroReason`     text DEFAULT null,
   `phase`          text NOT NULL DEFAULT 'U' CHECK(phase IN ( 'U' , 'S' , 'D' )),
   `flags`          integer NOT NULL DEFAULT 0,
   UNIQUE(`flightId`,`roundId`,`pilotId`,`judgeNum`),
   FOREIGN KEY(`pilotId`) REFERENCES `pilot`(`pilotId`),
   FOREIGN KEY(`flightId`) REFERENCES `flight`(`flightId`),
   FOREIGN KEY(`roundId`) REFERENCES `round`(`roundId`)
);

drop table scoredelta;
CREATE TABLE `scoredelta` (
   `sheetId`    integer NOT NULL,
   `deleted`    tinyint NOT NULL DEFAULT 0 CHECK(deleted IN ( 0 , 1 )),
   `figureNum`  inyint NOT NULL,
   `scoreTime`  integer NOT NULL,
   `breakFlag`  tinyint NOT NULL DEFAULT 0 CHECK(breakFlag IN ( 0 , 1 )),
   `flags`      integer NOT NULL DEFAULT 0,
   `score`      numeric,
   `comment`    text,
   `cdcomment`  text,
   PRIMARY KEY(`sheetId`,`figureNum`),
   FOREIGN KEY(`sheetId`) REFERENCES `sheet`(`sheetId`) ON DELETE CASCADE ON UPDATE CASCADE
);

drop table nextFlight;
CREATE TABLE "nextFlight" (
   `nextNoteFlightId`	integer NOT NULL,
   `nextCompId`	integer NOT NULL,
   `nextPilotId`	integer NOT NULL,
   FOREIGN KEY(`nextPilotId`) REFERENCES `pilot`(`pilotId`),
   PRIMARY KEY(`nextNoteFlightId`,`nextCompId`,`nextPilotId`)
);

insert into state values ('flightLineId', '1');
insert into state values ('flightLineName', 'Default Flightline 1');
insert into state values ('dbVersion', '2');
insert into user values ('judge', 'Judge 1', 'password', '', 'JUDGE');
insert into user values ('admin', 'Comp Admin', 'password', '', 'ADMIN,JUDGE');



