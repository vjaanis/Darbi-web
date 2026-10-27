* VFP 9 SP2. ASCII source; UTF-8 CSV input. No dependencies.
* r = import_darbi(csvPath, dbfPath, .T.) validates; .F. imports.
LPARAMETERS tcCsv, tcDbf, tlPreview
LOCAL loWorker, llPreview
llPreview = .T.
IF PCOUNT() >= 3 AND VARTYPE(m.tlPreview) = 'L'
    llPreview = m.tlPreview
ENDIF
loWorker = CREATEOBJECT('DwImporter')
RETURN m.loWorker.Run(m.tcCsv,m.tcDbf,m.llPreview)

DEFINE CLASS DwRow AS Custom
    DIMENSION v[24]
ENDDEFINE

DEFINE CLASS DwImporter AS Session
    DataSession = 2
    header = 'SAK_DAT;DARB_VEIDS;DARB_TIP1;DARBNOSAUK;G_DAT;CENA;MERVIEN;KLIENTS;KLIENT_ID;DARBN;DARB_ID;GAT;REKINS;REK_DAT;ACTIV;DAUDZ;SUMMA;ATLAIDE;LNG;PARSTV;DIVIZ;TERMINS;KOMENT'
    rownum = 0
    headerrows = 0
    fieldname = ''
    DIMENSION map[23], meta[1,18]

    FUNCTION Run(tcCsv,tcDbf,tlPreview)
        LOCAL r,e,rows,item,raw,csv,dbf,logfile,expno,stem,digits,i,j,k,n,nrf,cp
        LOCAL isauto,nextnr,firstnr,lastnr,tx,backup,name,val,logalias,body,ix,expected
        LOCAL ARRAY laMaximum[1],laFiles[1]
        r = CREATEOBJECT('Empty')
        ADDPROPERTY(m.r,'Ok',.F.)
        ADDPROPERTY(m.r,'Preview',m.tlPreview)
        ADDPROPERTY(m.r,'Count',0)
        ADDPROPERTY(m.r,'FirstNr',0)
        ADDPROPERTY(m.r,'LastNr',0)
        ADDPROPERTY(m.r,'ExportNr','')
        ADDPROPERTY(m.r,'BackupPath','')
        ADDPROPERTY(m.r,'Message','')
        tx = .F.
        SET TALK OFF
        SET SAFETY ON
        SET DELETED OFF
        SET EXCLUSIVE OFF
        SET MULTILOCKS ON
        SET REPROCESS TO 0
        SET CPDIALOG OFF
        SET POINT TO '.'
        SET SEPARATOR TO ','
        SET DATE TO DMY
        SET CENTURY ON
        SET COLLATE TO 'MACHINE'
        TRY
            IF VARTYPE(m.tcCsv)<>'C' OR VARTYPE(m.tcDbf)<>'C'
                ERROR 'Noradi CSV un DBF pilnos celus.'
            ENDIF
            csv=FULLPATH(m.tcCsv)
            dbf=FULLPATH(m.tcDbf)
            IF !FILE(m.csv) OR !FILE(m.dbf) OR !(UPPER(JUSTEXT(m.csv))=='CSV') OR !(UPPER(JUSTEXT(m.dbf))=='DBF')
                ERROR 'CSV vai DBF nav atrasts, vai neatbilst faila tips.'
            ENDIF
            IF UPPER(JUSTFNAME(m.dbf))=='WEB_IMPORTI.DBF'
                ERROR 'Merka tabula nedrikst but importa zurnals.'
            ENDIF
            stem=LOWER(JUSTSTEM(m.csv))
            IF LEFT(m.stem,6)<>'darbi_' OR SUBSTR(m.stem,17,10)<>'_eksports_'
                ERROR 'Saglabaj nosaukumu darbi_DD.MM.GGGG_eksports_000001.csv.'
            ENDIF
            =THIS.DateValue(SUBSTR(m.stem,7,10))
            digits=SUBSTR(m.stem,27)
            ix=AT(' (',m.digits)
            IF m.ix>0
                body=SUBSTR(m.digits,m.ix+2)
                IF RIGHT(m.body,1)<>')' OR !THIS.Digits(LEFT(m.body,LEN(m.body)-1))
                    ERROR 'Nederigs faila nosaukums.'
                ENDIF
                digits=LEFT(m.digits,m.ix-1)
            ENDIF
            IF !THIS.Digits(m.digits) OR LEN(m.digits)>10 OR VAL(m.digits)<1 OR VAL(m.digits)>2147483647
                ERROR 'Nederigs eksporta numurs.'
            ENDIF
            expno=ALLTRIM(STR(VAL(m.digits),10,0))
            m.r.ExportNr=m.expno
            =ADIR(laFiles,m.csv)
            IF laFiles[1,2]>5242880
                ERROR 'CSV parsniegti 5 MB.'
            ENDIF
            raw=FILETOSTR(m.csv)
            IF EMPTY(m.raw) OR CHR(0)$m.raw
                ERROR 'CSV ir tukss vai satur NUL baitus.'
            ENDIF
            IF m.tlPreview
                USE (m.dbf) IN 0 ALIAS dwiTarget SHARED NOUPDATE
            ELSE
                USE (m.dbf) IN 0 ALIAS dwiTarget EXCLUSIVE
            ENDIF
            cp=CPDBF('dwiTarget')
            IF m.cp=0
                ERROR 'DBF nav kodu lapas atzimes; kodejumu nedrikst minet.'
            ENDIF
            n=AFIELDS(THIS.meta,'dwiTarget')
            nrf=0
            FOR i=1 TO m.n
                IF UPPER(THIS.meta[m.i,1])=='NR'
                    nrf=m.i
                ENDIF
            ENDFOR
            IF m.nrf=0
                ERROR 'Nav NR lauka.'
            ENDIF
            IF THIS.meta[m.nrf,2]<>'I'
                ERROR 'NR jabut Integer tipam.'
            ENDIF
            isauto=THIS.meta[m.nrf,18]>0
            FOR j=1 TO 23
                k=0
                FOR i=1 TO m.n
                    IF UPPER(THIS.meta[m.i,1])==GETWORDNUM(THIS.header,m.j,';')
                        k=m.i
                        EXIT
                    ENDIF
                ENDFOR
                IF m.k=0
                    ERROR 'Trukst lauka '+GETWORDNUM(THIS.header,m.j,';')
                ENDIF
                name=GETWORDNUM(THIS.header,m.j,';')
                expected=IIF(INLIST(m.name,'SAK_DAT','G_DAT','REK_DAT','TERMINS'),'D',;
                    IIF(INLIST(m.name,'GAT','ACTIV'),'L',;
                    IIF(INLIST(m.name,'CENA','SUMMA'),'Y',;
                    IIF(m.name=='DAUDZ','N',;
                    IIF(INLIST(m.name,'KLIENT_ID','DARB_ID','REKINS','ATLAIDE'),'I','CVM')))))
                IF !(THIS.meta[m.k,2] $ m.expected)
                    ERROR 'Neatbilstosa lauka struktura: '+m.name
                ENDIF
                THIS.map[m.j]=m.k
            ENDFOR
            logfile=ADDBS(JUSTPATH(m.dbf))+'web_importi.dbf'
            IF FILE(m.logfile)
                IF m.tlPreview
                    USE (m.logfile) IN 0 ALIAS dwiLog SHARED NOUPDATE
                ELSE
                    USE (m.logfile) IN 0 ALIAS dwiLog EXCLUSIVE
                ENDIF
                =THIS.CheckLog(m.expno,m.raw)
            ENDIF
            rows=THIS.Parse(m.raw)
            FOR i=1 TO m.rows.Count
                THIS.rownum=m.i+THIS.headerrows
                item=m.rows.Item(m.i)
                FOR j=1 TO 23
                    k=THIS.map[m.j]
                    THIS.fieldname=THIS.meta[m.k,1]
                    m.item.v[m.j]=THIS.Convert(m.item.v[m.j],m.k,m.cp)
                ENDFOR
            ENDFOR
            THIS.rownum=0
            THIS.fieldname=''
            m.r.Count=m.rows.Count
            SELECT MAX(nr) FROM dwiTarget INTO ARRAY laMaximum
            nextnr=IIF(ISNULL(laMaximum[1]),0,MAX(0,laMaximum[1]))+1
            IF !m.isauto AND m.nextnr+m.rows.Count-1>2147483647
                ERROR 'NR diapazons ir izsmelts.'
            ENDIF
            IF m.tlPreview
                m.r.Ok=.T.
                m.r.Message='Parbaude sekmiga. Dati nav ierakstiti.'
            ELSE
                IF !USED('dwiLog')
                    CREATE TABLE (m.logfile) FREE (exp_nr C(10),file_name C(254),imported T,;
                        row_count I,first_nr I,last_nr I,csv_data M NOCPTRANS)
                    logalias=ALIAS()
                    USE IN (m.logalias)
                    USE (m.logfile) IN 0 ALIAS dwiLog EXCLUSIVE
                ENDIF
                =THIS.Transactable('dwiTarget')
                =THIS.Transactable('dwiLog')
                backup=ADDBS(JUSTPATH(m.dbf))+'web_backup_'+TTOC(DATETIME(),1)+'_'+SYS(2015)
                MD (m.backup)
                m.r.BackupPath=m.backup
                =THIS.Backup('dwiTarget',m.dbf,m.backup)
                =THIS.Backup('dwiLog',m.logfile,m.backup)
                BEGIN TRANSACTION
                tx=.T.
                firstnr=0
                lastnr=0
                FOR i=1 TO m.rows.Count
                    THIS.rownum=m.i+THIS.headerrows
                    item=m.rows.Item(m.i)
                    SELECT dwiTarget
                    APPEND BLANK
                    IF !m.isauto
                        REPLACE nr WITH m.nextnr
                        nextnr=m.nextnr+1
                    ENDIF
                    FOR j=1 TO 23
                        name=THIS.meta[THIS.map[m.j],1]
                        THIS.fieldname=m.name
                        val=m.item.v[m.j]
                        REPLACE (m.name) WITH m.val IN dwiTarget
                    ENDFOR
                    lastnr=dwiTarget.nr
                    IF m.i=1
                        firstnr=m.lastnr
                    ENDIF
                ENDFOR
                THIS.rownum=0
                THIS.fieldname=''
                INSERT INTO dwiLog (exp_nr,file_name,imported,row_count,first_nr,last_nr,csv_data);
                    VALUES (m.expno,JUSTFNAME(m.csv),DATETIME(),m.rows.Count,m.firstnr,m.lastnr,m.raw)
                END TRANSACTION
                tx=.F.
                m.r.Ok=.T.
                m.r.FirstNr=m.firstnr
                m.r.LastNr=m.lastnr
                m.r.Message='Imports pabeigts.'
            ENDIF
        CATCH TO e
            IF m.tx
                ROLLBACK
            ENDIF
            m.r.Message=IIF(THIS.rownum>0,'CSV ieraksts '+TRANSFORM(THIS.rownum)+': ','')+;
                IIF(EMPTY(THIS.fieldname),'',THIS.fieldname+': ')+m.e.Message
        FINALLY
            IF USED('dwiTarget')
                USE IN dwiTarget
            ENDIF
            IF USED('dwiLog')
                USE IN dwiLog
            ENDIF
        ENDTRY
        RETURN m.r
    ENDFUNC

    FUNCTION CheckLog(tcNumber,tcRaw)
        LOCAL i,names
        names='EXP_NR;FILE_NAME;IMPORTED;ROW_COUNT;FIRST_NR;LAST_NR;CSV_DATA'
        FOR i=1 TO 7
            IF TYPE('dwiLog.'+GETWORDNUM(m.names,m.i,';'))='U'
                ERROR 'web_importi.dbf struktura neatbilst importa zurnalam.'
            ENDIF
        ENDFOR
        SELECT dwiLog
        LOCATE FOR ALLTRIM(exp_nr)==m.tcNumber OR (LEN(csv_data)=LEN(m.tcRaw) AND csv_data==m.tcRaw)
        IF FOUND()
            ERROR 'Eksports vai identisks CSV jau importets. Atkartots imports blokets.'
        ENDIF
        RETURN .T.
    ENDFUNC

    FUNCTION Transactable(tcAlias)
        IF EMPTY(CURSORGETPROP('Database',m.tcAlias))
            IF !MAKETRANSACTABLE(m.tcAlias)
                ERROR 'Nevar ieslegt transakcijas: '+m.tcAlias
            ENDIF
        ENDIF
        RETURN .T.
    ENDFUNC

    FUNCTION Backup(tcAlias,tcDbf,tcFolder)
        LOCAL destination,recno
        destination=ADDBS(m.tcFolder)+JUSTFNAME(m.tcDbf)
        SELECT (m.tcAlias)
        SET ORDER TO 0
        COPY TO (m.destination) WITH CDX
        * COPY TO copies deleted rows as live rows; restore their deletion marks.
        USE (m.destination) IN 0 ALIAS dwiBackup EXCLUSIVE
        SELECT (m.tcAlias)
        SCAN FOR DELETED()
            recno=RECNO(m.tcAlias)
            GO m.recno IN dwiBackup
            DELETE IN dwiBackup
        ENDSCAN
        USE IN dwiBackup
        RETURN .T.
    ENDFUNC

    FUNCTION Parse(tcRaw)
        LOCAL text,ch,value,state,i,col,rec,rows,item,dirty,j,format
        text=m.tcRaw
        IF LEFT(m.text,3)==CHR(239)+CHR(187)+CHR(191)
            text=SUBSTR(m.text,4)
        ENDIF
        text=m.text+CHR(10)
        rows=CREATEOBJECT('Collection')
        item=CREATEOBJECT('DwRow')
        value=''
        state=0
        col=1
        rec=1
        format=0
        THIS.headerrows=0
        dirty=.F.
        i=1
        DO WHILE m.i<=LEN(m.text)
            ch=SUBSTR(m.text,m.i,1)
            THIS.rownum=m.rec
            DO CASE
            CASE m.state=2
                IF m.ch=='"'
                    IF SUBSTR(m.text,m.i+1,1)=='"'
                        value=m.value+'"'
                        i=m.i+1
                    ELSE
                        state=3
                    ENDIF
                ELSE
                    value=m.value+m.ch
                ENDIF
            CASE m.ch==';' OR m.ch==CHR(13) OR m.ch==CHR(10)
                m.item.v[m.col]=m.value
                value=''
                state=0
                IF m.ch==';'
                    col=m.col+1
                    dirty=.T.
                    IF m.col>24
                        ERROR 'CSV ierakstam vairak neka 24 lauki.'
                    ENDIF
                ELSE
                    IF m.dirty OR m.col>1
                        IF m.rec=1 AND m.col=23
                            FOR j=1 TO 23
                                IF !(m.item.v[m.j]==GETWORDNUM(THIS.header,m.j,';'))
                                    ERROR 'CSV galvene neatbilst VFP lauku secibai.'
                                ENDIF
                            ENDFOR
                            format=1
                            THIS.headerrows=1
                        ELSE
                            IF m.format=0
                                format=2
                            ENDIF
                            IF m.format=1
                                IF m.col<>23
                                    ERROR 'Vecaja CSV formata pec galvenes vajadzigi 23 lauki.'
                                ENDIF
                            ELSE
                                IF m.col<>24
                                    ERROR 'CSV bez galvenes vajadzigi 24 lauki ar tuksu NR.'
                                ENDIF
                                IF !(m.item.v[1]=='')
                                    ERROR 'CSV pirmajam NR laukam jabut tuksam.'
                                ENDIF
                                FOR j=1 TO 23
                                    m.item.v[m.j]=m.item.v[m.j+1]
                                ENDFOR
                            ENDIF
                            m.rows.Add(m.item)
                            IF m.rows.Count>200
                                ERROR 'Viena importa lidz 200 darbiem.'
                            ENDIF
                        ENDIF
                        rec=m.rec+1
                    ENDIF
                    item=CREATEOBJECT('DwRow')
                    col=1
                    dirty=.F.
                    IF m.ch==CHR(13) AND SUBSTR(m.text,m.i+1,1)==CHR(10)
                        i=m.i+1
                    ENDIF
                ENDIF
            CASE m.state=3
                ERROR 'Pec pedinas jabut atdalitajam vai rindas beigam.'
            CASE m.ch=='"'
                IF m.state<>0
                    ERROR 'Pedina neatlauta neciteta lauka.'
                ENDIF
                state=2
                dirty=.T.
            OTHERWISE
                state=1
                dirty=.T.
                value=m.value+m.ch
            ENDCASE
            IF LEN(m.value)>65536
                ERROR 'CSV lauks ir parak gars.'
            ENDIF
            i=m.i+1
        ENDDO
        IF m.state=2
            ERROR 'CSV nav aizverosas pedinas.'
        ENDIF
        IF m.rows.Count=0
            ERROR 'CSV nav darbu.'
        ENDIF
        RETURN m.rows
    ENDFUNC

    FUNCTION Convert(tcValue,tnMeta,tnTarget)
        LOCAL typ,text,encoded,number,scale,whole,frac,unsigned,negative,canonical,maxval
        typ=THIS.meta[m.tnMeta,2]
        DO CASE
        CASE INLIST(m.typ,'C','V','M')
            IF THIS.meta[m.tnMeta,6]
                ERROR 'Teksta laukam nedrikst but NOCPTRANS.'
            ENDIF
            text=STRCONV(m.tcValue,11,CPCURRENT(),1)
            IF !(STRCONV(m.text,9,CPCURRENT(),1)==m.tcValue)
                ERROR 'Tekstu nevar attelot VFP kodu lapa '+TRANSFORM(CPCURRENT())+'.'
            ENDIF
            encoded=CPCONVERT(CPCURRENT(),m.tnTarget,m.text)
            IF !(CPCONVERT(m.tnTarget,CPCURRENT(),m.encoded)==m.text)
                ERROR 'Tekstu nevar saglabat DBF kodu lapa '+TRANSFORM(m.tnTarget)+'.'
            ENDIF
            IF m.typ<>'M' AND LEN(m.encoded)>THIS.meta[m.tnMeta,3]
                ERROR 'Teksts '+TRANSFORM(LEN(m.encoded))+' baiti; lauka ietilpiba '+;
                    TRANSFORM(THIS.meta[m.tnMeta,3])+'. Dati nav apgriezti.'
            ENDIF
            RETURN m.text
        CASE m.typ='D'
            RETURN THIS.DateValue(m.tcValue)
        CASE m.typ='L'
            IF !(m.tcValue=='0' OR m.tcValue=='1')
                ERROR 'Logiskajam laukam atlauts tikai 0 vai 1.'
            ENDIF
            RETURN m.tcValue=='1'
        CASE INLIST(m.typ,'I','N','Y')
            IF EMPTY(m.tcValue)
                RETURN IIF(THIS.meta[m.tnMeta,5],.NULL.,0)
            ENDIF
            negative=LEFT(m.tcValue,1)=='-'
            unsigned=IIF(m.negative,SUBSTR(m.tcValue,2),m.tcValue)
            whole=GETWORDNUM(m.unsigned,1,'.')
            frac=IIF('.'$m.unsigned,SUBSTR(m.unsigned,AT('.',m.unsigned)+1),'')
            IF OCCURS('.',m.unsigned)>1 OR !THIS.Digits(m.whole) OR LEFT(m.unsigned,1)=='.' OR ;
                (!EMPTY(m.frac) AND !THIS.Digits(m.frac)) OR RIGHT(m.unsigned,1)=='.'
                ERROR 'Nederigs skaitlis; decimallam lieto punktu.'
            ENDIF
            scale=IIF(m.typ='Y',4,THIS.meta[m.tnMeta,4])
            IF LEN(m.frac)>m.scale
                ERROR 'Parak daudz zimes aiz komata; skaitlis netiks noapalots.'
            ENDIF
            DO WHILE LEN(m.whole)>1 AND LEFT(m.whole,1)=='0'
                whole=SUBSTR(m.whole,2)
            ENDDO
            canonical=IIF(m.negative,'-','')+m.whole+IIF(m.scale>0,'.'+PADR(m.frac,m.scale,'0'),'')
            DO CASE
            CASE m.typ='I'
                maxval=IIF(m.negative,'2147483648','2147483647')
                IF LEN(m.whole)>10 OR (LEN(m.whole)=10 AND m.whole>m.maxval)
                    ERROR 'Skaitlis neietilpst Integer lauka.'
                ENDIF
                RETURN VAL(m.canonical)
            CASE m.typ='Y'
                maxval='922337203685477.5807'
                unsigned=m.whole+'.'+PADR(m.frac,4,'0')
                IF LEN(m.unsigned)>LEN(m.maxval) OR (LEN(m.unsigned)=LEN(m.maxval) AND m.unsigned>m.maxval)
                    ERROR 'Skaitlis neietilpst Currency lauka.'
                ENDIF
                RETURN THIS.CurrencyValue(m.whole+PADR(m.frac,4,'0'),m.negative)
            OTHERWISE
                IF LEN(m.canonical)>THIS.meta[m.tnMeta,3]
                    ERROR 'Skaitlis neietilpst Numeric lauka.'
                ENDIF
                number=VAL(m.canonical)
                IF ALLTRIM(STR(m.number,THIS.meta[m.tnMeta,3],m.scale))<>m.canonical
                    ERROR 'Skaitli nevar saglabat bez precizitates zuduma.'
                ENDIF
                RETURN m.number
            ENDCASE
        OTHERWISE
            ERROR 'Neatbalstits lauka tips: '+m.typ
        ENDCASE
    ENDFUNC

    FUNCTION CurrencyValue(tcDigits,tlNegative)
        * Convert decimal digits directly to int64 bytes. No floating-point money conversion.
        LOCAL digits,quotient,bytes,carry,i,j,qdigit,byteval
        digits=m.tcDigits
        bytes=''
        FOR i=1 TO 8
            quotient=''
            carry=0
            FOR j=1 TO LEN(m.digits)
                carry=m.carry*10+VAL(SUBSTR(m.digits,m.j,1))
                qdigit=INT(m.carry/256)
                quotient=m.quotient+CHR(48+m.qdigit)
                carry=MOD(m.carry,256)
            ENDFOR
            bytes=m.bytes+CHR(m.carry)
            DO WHILE LEN(m.quotient)>1 AND LEFT(m.quotient,1)=='0'
                quotient=SUBSTR(m.quotient,2)
            ENDDO
            digits=m.quotient
        ENDFOR
        IF m.tlNegative
            carry=1
            FOR i=1 TO 8
                byteval=255-ASC(SUBSTR(m.bytes,m.i,1))+m.carry
                bytes=STUFF(m.bytes,m.i,1,CHR(MOD(m.byteval,256)))
                carry=INT(m.byteval/256)
            ENDFOR
        ENDIF
        RETURN CTOBIN(m.bytes,'YRS')
    ENDFUNC

    FUNCTION Digits(tcText)
        RETURN LEN(m.tcText)>0 AND LEN(CHRTRAN(m.tcText,'0123456789',''))=0
    ENDFUNC

    FUNCTION DateValue(tcText)
        LOCAL dayno,monthno,yearno,dateval
        IF EMPTY(m.tcText)
            RETURN {}
        ENDIF
        IF LEN(m.tcText)<>10 OR SUBSTR(m.tcText,3,1)<>'.' OR SUBSTR(m.tcText,6,1)<>'.' OR ;
            !THIS.Digits(LEFT(m.tcText,2)+SUBSTR(m.tcText,4,2)+RIGHT(m.tcText,4))
            ERROR 'Datuma formats ir DD.MM.GGGG.'
        ENDIF
        dayno=VAL(LEFT(m.tcText,2))
        monthno=VAL(SUBSTR(m.tcText,4,2))
        yearno=VAL(RIGHT(m.tcText,4))
        IF m.yearno<100 OR m.yearno>9999 OR m.monthno<1 OR m.monthno>12 OR m.dayno<1 OR m.dayno>31
            ERROR 'Nederigs datums.'
        ENDIF
        dateval=DATE(m.yearno,m.monthno,m.dayno)
        IF EMPTY(m.dateval) OR DAY(m.dateval)<>m.dayno OR MONTH(m.dateval)<>m.monthno
            ERROR 'Nederigs datums.'
        ENDIF
        RETURN m.dateval
    ENDFUNC
ENDDEFINE
