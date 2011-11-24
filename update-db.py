#!/usr/bin/python

# This script updates the database to handle version 0.4.0 stuff.
# Since this is the first change to the database schema, we don't really have to check anything

import sqlite3
db = sqlite3.connect('/etc/openstatus/openstatus.db', check_same_thread = False, isolation_level=None)
db.row_factory=sqlite3.Row
sql = db.cursor()
try:
	sql.execute('CREATE TABLE IF NOT EXISTS "history" ( "uid" INTEGER, "time" INTEGER, "mtotal" TEXT, "mused" TEXT, "mfree" TEXT, "mbuffers" TEXT, "disktotal" TEXT, "diskused" TEXT, "diskfree" TEXT, "load1" TEXT, "load5" TEXT, "load15" TEXT, "rx" TEXT, "tx" TEXT)')
	sql.execute('CREATE TABLE IF NOT EXISTS "history5" ( "uid" INTEGER, "time" INTEGER, "mtotal" TEXT, "mused" TEXT, "mfree" TEXT, "mbuffers" TEXT, "disktotal" TEXT, "diskused" TEXT, "diskfree" TEXT, "load1" TEXT, "load5" TEXT, "load15" TEXT, "rx" TEXT, "tx" TEXT)')
	sql.execute('CREATE TABLE IF NOT EXISTS "history10" ( "uid" INTEGER, "time" INTEGER, "mtotal" TEXT, "mused" TEXT, "mfree" TEXT, "mbuffers" TEXT, "disktotal" TEXT, "diskused" TEXT, "diskfree" TEXT, "load1" TEXT, "load5" TEXT, "load15" TEXT, "rx" TEXT, "tx" TEXT)')

	sql.execute('ALTER TABLE servers ADD COLUMN "provider" TEXT;')
	sql.execute('ALTER TABLE servers ADD COLUMN "node" TEXT;')
	sql.execute('ALTER TABLE servers ADD COLUMN "tx" TEXT;')
	sql.execute('ALTER TABLE servers ADD COLUMN "rx" TEXT;')
except:
	pass