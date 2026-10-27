* Run in VFP 9: DO palaist_importu.prg
LOCAL lcFolder,lcCsv,lcDbf,loResult,lcMessage,lcOldPath
lcFolder=ADDBS(JUSTPATH(FULLPATH(SYS(16))))
lcOldPath=SET('PATH')
TRY
    SET PATH TO (m.lcFolder) ADDITIVE
    lcCsv=GETFILE('csv','Darba-web eksporta CSV','Izveleties',0,'Izvelies eksportu')
    IF EMPTY(m.lcCsv)
        RETURN
    ENDIF
    lcDbf=GETFILE('dbf','VFP merka tabula darbi.dbf','Izveleties',0,'Izvelies DARBI tabulu')
    IF EMPTY(m.lcDbf)
        RETURN
    ENDIF
    loResult=import_darbi(m.lcCsv,m.lcDbf,.T.)
    IF !m.loResult.Ok
        =MESSAGEBOX(m.loResult.Message,16,'Imports nav veikts')
        RETURN
    ENDIF
    lcMessage='Parbaude sekmiga.'+CHR(13)+CHR(10)+;
        'Eksports: '+m.loResult.ExportNr+CHR(13)+CHR(10)+;
        'Darbu skaits: '+TRANSFORM(m.loResult.Count)+CHR(13)+CHR(10)+;
        'Merka tabula: '+m.lcDbf+CHR(13)+CHR(10)+CHR(13)+CHR(10)+;
        'Pirms ierakstisanas tiks izveidota rezerves kopija.'+CHR(13)+CHR(10)+;
        'Importet sos darbus?'
    IF MESSAGEBOX(m.lcMessage,36,'Datu imports')<>6
        RETURN
    ENDIF
    * Actual import repeats validation under an exclusive lock.
    loResult=import_darbi(m.lcCsv,m.lcDbf,.F.)
    IF m.loResult.Ok
        =MESSAGEBOX('Importeti '+TRANSFORM(m.loResult.Count)+' darbi.'+CHR(13)+CHR(10)+;
            'VFP NR: '+TRANSFORM(m.loResult.FirstNr)+' - '+TRANSFORM(m.loResult.LastNr)+CHR(13)+CHR(10)+;
            'Rezerves kopija: '+m.loResult.BackupPath,64,'Imports pabeigts')
    ELSE
        =MESSAGEBOX(m.loResult.Message,16,'Imports nav veikts')
    ENDIF
FINALLY
    SET PATH TO (m.lcOldPath)
ENDTRY
