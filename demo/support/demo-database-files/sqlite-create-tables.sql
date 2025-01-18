CREATE TABLE "person" (
	"id" INTEGER PRIMARY KEY AUTOINCREMENT,
	"name" VARCHAR(200) NOT NULL,
	"short_name" VARCHAR(20) NOT NULL,
	"gender_id" INTEGER NOT NULL DEFAULT 0,
	"nationality_id" INTEGER NOT NULL DEFAULT 0,
	"birthdate" DATE DEFAULT NULL,
	"resume" TEXT DEFAULT NULL,
	"url" VARCHAR(100) DEFAULT NULL
)
;
CREATE UNIQUE INDEX short_name_unique
ON person(short_name)
;
CREATE INDEX person_name
ON person(name)
;
CREATE TABLE "gender" (
	"id" INTEGER PRIMARY KEY AUTOINCREMENT,
	"name" VARCHAR(50) NOT NULL
)
;
CREATE UNIQUE INDEX gender_unique
ON gender(name)
;
CREATE TABLE "nationality" (
	"id" INTEGER PRIMARY KEY AUTOINCREMENT,
	"name" VARCHAR(100) NOT NULL
)
;
CREATE UNIQUE INDEX nationality_unique
ON nationality(name)
;
CREATE TABLE "tags" (
	"id" INTEGER PRIMARY KEY AUTOINCREMENT,
	"name" VARCHAR(100) NOT NULL
)
;
CREATE UNIQUE INDEX tags_unique
ON tags(name)
;

CREATE TABLE "person_tags" (
	"id" INTEGER PRIMARY KEY AUTOINCREMENT,
	"person_id" INTEGER NOT NULL DEFAULT "0",
	"tags_id" INTEGER NOT NULL DEFAULT "0"
)
;
CREATE UNIQUE INDEX person_tags_unique
ON person_tags(person_id,tags_id)
;
