* VFP 9 SP2 -> Darba-web clients. ASCII source, UTF-8 semicolon CSV with header.
* DO eksportet_klientus.prg    && choose source DBF and destination CSV
* r = eksportet_klientus(sourceDbf, destinationCsv)
LPARAMETERS tcDbf,tcCsv
LOCAL interactive,worker,result,source,destination
interactive=PCOUNT()<2
source=m.tcDbf
destination=m.tcCsv
IF m.interactive
    source=GETFILE('dbf','VFP klientu tabula','Izveleties',0,'Izvelies klientu registru')
    IF EMPTY(m.source)
        RETURN .F.
    ENDIF
    destination=PUTFILE('Saglabat klientu eksportu','klienti_'+DTOS(DATE())+'.csv','csv')
    IF EMPTY(m.destination)
        RETURN .F.
    ENDIF
ENDIF
worker=CREATEOBJECT('DwClientExporter')
result=m.worker.Run(m.source,m.destination)
IF m.interactive
    =MESSAGEBOX(m.result.Message,IIF(m.result.Ok,64,16),'Klientu eksports uz Web')
ENDIF
RETURN m.result

DEFINE CLASS DwClientExporter AS Session
    DataSession=2
    fields='ID;NAME;C_CODE;VAT;LNG;ADDRESS;LIG_NR;BANK;SWIFT;B_ACC;MAKS_LAIKS;EMAIL;MOB;PFONE;NICKNAME;PERSON_1;PERSON_2;PERSON_3;PERSON_4;PVN_MAKS;DALITAIS_R'
    FUNCTION Run(tcDbf,tcCsv)
        LOCAL r,e,output,fieldcount,i,j,k,name,value,line,cell,rows,raw,idvalue
        LOCAL ARRAY fieldmeta[1],fieldmap[21]
        r=CREATEOBJECT('Empty')
        ADDPROPERTY(m.r,'Ok',.F.)
        ADDPROPERTY(m.r,'Count',0)
        ADDPROPERTY(m.r,'Message','')
        SET TALK OFF
        SET DELETED ON
        SET CPDIALOG OFF
        TRY
            IF VARTYPE(m.tcDbf)<>'C' OR VARTYPE(m.tcCsv)<>'C' OR !FILE(m.tcDbf)
                ERROR 'Noradi klientu DBF un merka CSV failu.'
            ENDIF
            output=FORCEEXT(FULLPATH(m.tcCsv),'csv')
            IF UPPER(FULLPATH(m.tcDbf))==UPPER(m.output)
                ERROR 'Avota un merka faili sakrit.'
            ENDIF
            USE (m.tcDbf) IN 0 ALIAS dwClients SHARED NOUPDATE
            IF CPDBF('dwClients')=0 OR CPDBF('dwClients')<>CPCURRENT()
                ERROR 'VFP aktivajai kodu lapai jasakrit ar klientu DBF kodu lapu. Baltic datiem parasti CODEPAGE=1257.'
            ENDIF
            fieldcount=AFIELDS(fieldmeta,'dwClients')
            FOR i=1 TO 21
                name=GETWORDNUM(THIS.fields,m.i,';')
                k=0
                FOR j=1 TO m.fieldcount
                    IF UPPER(fieldmeta[m.j,1])==m.name
                        k=m.j
                        EXIT
                    ENDIF
                ENDFOR
                IF m.k=0
                    ERROR 'VFP klientu tabula trukst lauka '+m.name
                ENDIF
                fieldmap[m.i]=m.k
            ENDFOR
            raw=CHR(239)+CHR(187)+CHR(191)+THIS.fields+CHR(13)+CHR(10)
            rows=0
            SELECT dwClients
            SET ORDER TO 0
            SCAN
                line=''
                FOR i=1 TO 21
                    name=GETWORDNUM(THIS.fields,m.i,';')
                    value=EVALUATE('dwClients.'+m.name)
                    IF INLIST(m.name,'PVN_MAKS','DALITAIS_R')
                        IF ISNULL(m.value)
                            cell='0'
                        ELSE
                            IF !INLIST(VARTYPE(m.value),'L','N','I','Y')
                                ERROR 'Lauks '+m.name+' nav logisks/skaitlisks.'
                            ENDIF
                            IF VARTYPE(m.value)=='L'
                                cell=IIF(m.value,'1','0')
                            ELSE
                                IF !INLIST(m.value,0,1)
                                    ERROR 'Laukam '+m.name+' jabut 0 vai 1.'
                                ENDIF
                                cell=IIF(m.value=1,'1','0')
                            ENDIF
                        ENDIF
                    ELSE
                        IF INLIST(m.name,'ID','MAKS_LAIKS')
                            IF ISNULL(m.value)
                                cell=''
                            ELSE
                                IF !INLIST(VARTYPE(m.value),'N','I','Y') OR m.value<>INT(m.value)
                                    ERROR 'Laukam '+m.name+' jabut veselam skaitlim.'
                                ENDIF
                                IF m.name=='ID' AND (m.value<1 OR m.value>2147483647)
                                    ERROR 'VFP klienta ID nav derigs.'
                                ENDIF
                                cell=ALLTRIM(STR(m.value,20,0))
                            ENDIF
                        ELSE
                            IF ISNULL(m.value)
                                cell=''
                            ELSE
                                IF !INLIST(VARTYPE(m.value),'C','M')
                                    ERROR 'Laukam '+m.name+' jabut tekstam.'
                                ENDIF
                                cell=STRCONV(RTRIM(m.value),9,CPCURRENT(),1)
                            ENDIF
                        ENDIF
                    ENDIF
                    IF m.i>1
                        line=m.line+';'
                    ENDIF
                    line=m.line+'"'+STRTRAN(m.cell,'"','""')+'"'
                ENDFOR
                raw=m.raw+m.line+CHR(13)+CHR(10)
                rows=m.rows+1
                IF m.rows>10000 OR LEN(m.raw)>2097152
                    ERROR 'Eksports parsniegtu Web limita 10 000 klientus vai 2 MB.'
                ENDIF
            ENDSCAN
            IF m.rows=0
                ERROR 'VFP tabula nav nedzestu klientu.'
            ENDIF
            IF STRTOFILE(m.raw,m.output)<>LEN(m.raw)
                ERROR 'Neizdevas saglabat pilnu CSV.'
            ENDIF
            m.r.Ok=.T.
            m.r.Count=m.rows
            m.r.Message='Eksporteti '+TRANSFORM(m.rows)+' klienti: '+m.output
        CATCH TO e
            m.r.Message=m.e.Message
        FINALLY
            IF USED('dwClients')
                USE IN dwClients
            ENDIF
        ENDTRY
        RETURN m.r
    ENDFUNC
ENDDEFINE
