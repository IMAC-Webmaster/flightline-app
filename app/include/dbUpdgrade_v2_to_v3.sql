PRAGMA foreign_keys=off;

BEGIN TRANSACTION;

ALTER TABLE score RENAME TO _score_old;

CREATE TABLE `score` (
     `sheetId`            integer NOT NULL,
     `figureNum`          tinyint NOT NULL,
     `scoreTime`          integer NOT NULL,
     `breakFlag`          tinyint NOT NULL DEFAULT 0 CHECK(breakFlag IN ( 0 , 1 )),
     `notObservedFlag`    tinyint NOT NULL DEFAULT 0 CHECK(breakFlag IN ( 0 , 1 )),
     `score`              numeric,
     `comment`            text,
     PRIMARY KEY(`sheetId`,`figureNum`),
     FOREIGN KEY(`sheetId`) REFERENCES `sheet`(`sheetId`) ON DELETE CASCADE ON UPDATE CASCADE
);

INSERT INTO score (sheetId, figureNum, scoreTime, breakFlag, score, comment)
    SELECT sheetId, figureNum, scoreTime, breakFlag, score, comment
FROM _score_old;

ALTER TABLE scoredelta RENAME TO _scoredelta_old;

CREATE TABLE `scoredelta` (
      `sheetId`            integer NOT NULL,
      `deleted`            tinyint NOT NULL DEFAULT 0 CHECK(deleted IN ( 0 , 1 )),
      `figureNum`          inyint NOT NULL,
      `scoreTime`          integer NOT NULL,
      `breakFlag`          tinyint NOT NULL DEFAULT 0 CHECK(breakFlag IN ( 0 , 1 )),
      `notObservedFlag`    tinyint NOT NULL DEFAULT 0 CHECK(breakFlag IN ( 0 , 1 )),
      `score`              numeric,
      `comment`            text,
      `cdcomment`          text,
      PRIMARY KEY(`sheetId`,`figureNum`),
      FOREIGN KEY(`sheetId`) REFERENCES `sheet`(`sheetId`) ON DELETE CASCADE ON UPDATE CASCADE
);

INSERT INTO scoredelta (sheetId, deleted, figureNum, scoreTime, breakFlag, score, comment, cdcomment)
SELECT sheetId, deleted, figureNum, scoreTime, breakFlag, score, comment, cdcomment
FROM _scoredelta_old;

UPDATE state set value='3' where key='dbversion';
UPDATE score set notObservedFlag = 1 where score is null;

DROP TABLE _scoredelta_old;
DROP TABLE _score_old;
COMMIT;

PRAGMA foreign_keys=on;