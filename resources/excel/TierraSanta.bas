Attribute VB_Name = "TierraSanta"
' Macro de los libros Excel del sistema (recursos, insumos, empleados).
' La hoja oculta "_tsh" trae la dirección del sistema (A1), la clave de acceso personal (A2), el libro (A3), las hojas
' del archivo (A4), la hoja índice (A5), el correo del usuario (A6), el vencimiento (A7) y, desde la fila 10, cada hoja
' con su clave (A = título, B = clave).
' Botones: en el índice "Actualizar todo" / "Subir cambios"; en cada hoja "Actualizar hoja" / "Subir hoja".
' "Subir" solo aparece despues de "Actualizar" (en cada apertura): siempre se trabaja sobre datos frescos.
Option Explicit

Private Const HOJA_CFG As String = "_tsh"

Public Sub Auto_Open()
    On Error Resume Next
    CrearBotones
    MostrarSubir "", False
End Sub

' ------------------------------------------------------------------ configuracion

Private Function Cfg(ByVal fila As Long) As String
    Cfg = CStr(ThisWorkbook.Worksheets(HOJA_CFG).Cells(fila, 1).Value)
End Function

Private Function ClaveDeHoja(ByVal nombre As String) As String
    Dim ws As Worksheet, f As Long
    Set ws = ThisWorkbook.Worksheets(HOJA_CFG)
    f = 10
    Do While CStr(ws.Cells(f, 1).Value) <> ""
        If CStr(ws.Cells(f, 1).Value) = nombre Then
            ClaveDeHoja = CStr(ws.Cells(f, 2).Value)
            Exit Function
        End If
        f = f + 1
    Loop
    ClaveDeHoja = ""
End Function

Private Function ExisteHoja(ByVal nombre As String) As Boolean
    Dim ws As Worksheet
    On Error Resume Next
    Set ws = ThisWorkbook.Worksheets(nombre)
    ExisteHoja = Not ws Is Nothing
End Function

' ------------------------------------------------------------------ botones

Private Sub CrearBotones()
    Dim ws As Worksheet, izquierda As Double
    For Each ws In ThisWorkbook.Worksheets
        If ws.Name = Cfg(5) Then
            AgregarBoton ws, "tsh_actualizar", "Actualizar todo", "ActualizarTodo", 8, 6, 130
            AgregarBoton ws, "tsh_subir", "Subir cambios", "SubirTodo", 146, 6, 130
        ElseIf ClaveDeHoja(ws.Name) <> "" Then
            izquierda = ws.Cells(1, ws.UsedRange.Columns.Count + 2).Left
            AgregarBoton ws, "tsh_actualizar", "Actualizar hoja", "ActualizarHoja", izquierda, 4, 110
            AgregarBoton ws, "tsh_subir", "Subir hoja", "SubirHoja", izquierda + 116, 4, 100
        End If
    Next ws
End Sub

Private Sub AgregarBoton(ws As Worksheet, ByVal nombre As String, ByVal texto As String, ByVal accion As String, _
        ByVal izquierda As Double, ByVal arriba As Double, ByVal ancho As Double)
    Dim b As Object
    On Error Resume Next
    Set b = ws.Buttons(nombre)
    On Error GoTo 0
    If b Is Nothing Then
        Set b = ws.Buttons.Add(izquierda, arriba, ancho, 22)
        b.Name = nombre
    End If
    b.Caption = texto
    b.OnAction = accion
    b.Placement = 3 ' no se mueve ni cambia de tamaño con las celdas
End Sub

' hoja = "" -> todas
Private Sub MostrarSubir(ByVal hoja As String, ByVal visible As Boolean)
    Dim ws As Worksheet
    On Error Resume Next
    For Each ws In ThisWorkbook.Worksheets
        If hoja = "" Or ws.Name = hoja Then
            ws.Buttons("tsh_subir").Visible = visible
        End If
    Next ws
End Sub

' ------------------------------------------------------------------ actualizar

Public Sub ActualizarTodo()
    If MsgBox("Se traerán los datos actuales del sistema y reemplazarán los de todas las hojas." & vbLf & _
              "Si ya hiciste cambios en este archivo, se perderán." & vbLf & vbLf & "¿Continuar?", _
              vbYesNo + vbQuestion, "Actualizar") = vbNo Then Exit Sub
    If TraerDatos(Cfg(4)) Then
        MostrarSubir "", True
        MsgBox "Listo: datos al " & Format(Now, "dd/mm/yyyy hh:nn") & "." & vbLf & _
               "Haz tus cambios y luego usa ""Subir cambios"".", vbInformation, "Actualizar"
    End If
End Sub

Public Sub ActualizarHoja()
    Dim clave As String, nombre As String
    nombre = ActiveSheet.Name
    clave = ClaveDeHoja(nombre)
    If clave = "" Then Exit Sub
    If MsgBox("Se traerán los datos actuales de """ & nombre & """ y reemplazarán los de esta hoja." & vbLf & vbLf & _
              "¿Continuar?", vbYesNo + vbQuestion, "Actualizar hoja") = vbNo Then Exit Sub
    If TraerDatos(clave) Then
        MostrarSubir nombre, True
        MsgBox "Listo: """ & nombre & """ al " & Format(Now, "dd/mm/yyyy hh:nn") & ".", vbInformation, "Actualizar hoja"
    End If
End Sub

' Descarga el Excel fresco de esas hojas y copia sus valores (los formatos y listas de este archivo se conservan).
Private Function TraerDatos(ByVal hojas As String) As Boolean
    Dim ruta As String, wb As Workbook, ws As Worksheet, destino As Worksheet, filas As Long, cols As Long, p As Object
    ruta = Environ("TEMP") & "\tsh_" & Format(Now, "yyyymmddhhnnss") & ".xlsx"
    If Not Descargar(Cfg(1) & "/api/libros/" & Cfg(3) & "/excel?hojas=" & hojas, ruta) Then Exit Function

    Application.ScreenUpdating = False
    Application.DisplayAlerts = False
    Set wb = Workbooks.Open(ruta, ReadOnly:=True)
    For Each ws In wb.Worksheets
        If ws.Name <> Cfg(5) And ExisteHoja(ws.Name) Then
            Set destino = ThisWorkbook.Worksheets(ws.Name)
            filas = ws.UsedRange.Rows.Count
            cols = ws.UsedRange.Columns.Count
            destino.Rows("2:" & destino.Rows.Count).ClearContents
            destino.Range("A1").Resize(filas, cols).Value = ws.Range("A1").Resize(filas, cols).Value
        End If
    Next ws
    ' Firma de cada hoja traída (marca de versión): el sistema la compara al subir, hoja por hoja
    For Each p In wb.CustomDocumentProperties
        If Left(p.Name, 10) = "tsh_firma_" Or p.Name = "tsh_descargado" Then GuardarPropiedad p.Name, CStr(p.Value)
    Next p
    wb.Close False
    Kill ruta
    Application.DisplayAlerts = True
    Application.ScreenUpdating = True
    TraerDatos = True
End Function

Private Sub GuardarPropiedad(ByVal nombre As String, ByVal valor As String)
    On Error Resume Next
    ThisWorkbook.CustomDocumentProperties(nombre).Value = valor
    If Err.Number <> 0 Then
        Err.Clear
        ThisWorkbook.CustomDocumentProperties.Add Name:=nombre, LinkToContent:=False, Type:=4, Value:=valor
    End If
End Sub

' ------------------------------------------------------------------ subir

Public Sub SubirTodo()
    Subir Cfg(4), "todas las hojas"
End Sub

Public Sub SubirHoja()
    Dim clave As String
    clave = ClaveDeHoja(ActiveSheet.Name)
    If clave <> "" Then Subir clave, """" & ActiveSheet.Name & """"
End Sub

' Envia una copia del archivo: el sistema la revisa (nuevos / cambios / errores) y, si se confirma, la guarda.
Private Sub Subir(ByVal hojas As String, ByVal descripcion As String)
    Dim ruta As String, respuesta As String, lineas() As String, id As String, puede As Boolean, resumen As String, i As Long
    ruta = Environ("TEMP") & "\tsh_subir_" & Format(Now, "yyyymmddhhnnss") & ".xlsm"
    ThisWorkbook.SaveCopyAs ruta
    respuesta = Enviar("POST", Cfg(1) & "/api/libros/" & Cfg(3) & "/revisar?hojas=" & hojas, ruta)
    Kill ruta
    If respuesta = "" Then Exit Sub

    lineas = Split(Replace(respuesta, vbCr, ""), vbLf)
    For i = 0 To UBound(lineas)
        If Left(lineas(i), 3) = "ID:" Then id = Mid(lineas(i), 4)
        If Left(lineas(i), 8) = "GUARDAR:" Then puede = (Mid(lineas(i), 9) = "1")
        If Left(lineas(i), 8) = "RESUMEN:" Then resumen = Mid(lineas(i), 9)
    Next i
    EscribirResultado lineas

    If Not puede Then
        MsgBox resumen & vbLf & vbLf & "No hay nada que guardar o hay que corregir algo: revisa la hoja ""Resultado"".", _
               vbExclamation, "Subir " & descripcion
        Exit Sub
    End If
    If MsgBox(resumen & vbLf & vbLf & "Detalle en la hoja ""Resultado"". Las filas con errores no se guardan." & vbLf & vbLf & _
              "¿Guardar estos cambios en el sistema?", vbYesNo + vbQuestion, "Subir " & descripcion) = vbNo Then Exit Sub

    respuesta = Enviar("POST", Cfg(1) & "/api/libros/" & Cfg(3) & "/guardar/" & id, "")
    If respuesta <> "" Then MsgBox respuesta, vbInformation, "Subir " & descripcion
End Sub

Private Sub EscribirResultado(lineas() As String)
    Dim ws As Worksheet, i As Long, f As Long
    If Not ExisteHoja("Resultado") Then
        Set ws = ThisWorkbook.Worksheets.Add(After:=ThisWorkbook.Worksheets(ThisWorkbook.Worksheets.Count))
        ws.Name = "Resultado"
    Else
        Set ws = ThisWorkbook.Worksheets("Resultado")
        ws.Cells.ClearContents
    End If
    ws.Cells(1, 1).Value = "Resultado de la revisión (" & Format(Now, "dd/mm/yyyy hh:nn") & ")"
    ws.Cells(1, 1).Font.Bold = True
    f = 3
    For i = 0 To UBound(lineas)
        If Left(lineas(i), 3) <> "ID:" And Left(lineas(i), 8) <> "GUARDAR:" Then
            ws.Cells(f, 1).Value = "'" & lineas(i)
            f = f + 1
        End If
    Next i
    ws.Columns(1).ColumnWidth = 140
End Sub

' ------------------------------------------------------------------ HTTP

Private Function Descargar(ByVal url As String, ByVal ruta As String) As Boolean
    Dim h As Object, s As Object
    Set h = Peticion("GET", url, Empty)
    If h Is Nothing Then Exit Function
    Set s = CreateObject("ADODB.Stream")
    s.Type = 1
    s.Open
    s.Write h.responseBody
    s.SaveToFile ruta, 2
    s.Close
    Descargar = True
End Function

' Devuelve el texto de la respuesta ("" si hubo error, ya avisado)
Private Function Enviar(ByVal metodo As String, ByVal url As String, ByVal rutaArchivo As String) As String
    Dim h As Object, cuerpo As Variant, s As Object
    If rutaArchivo <> "" Then
        Set s = CreateObject("ADODB.Stream")
        s.Type = 1
        s.Open
        s.LoadFromFile rutaArchivo
        cuerpo = s.Read
        s.Close
    End If
    Set h = Peticion(metodo, url, cuerpo)
    If h Is Nothing Then Exit Function
    Enviar = h.responseText
End Function

Private Function Peticion(ByVal metodo As String, ByVal url As String, cuerpo As Variant) As Object
    Dim h As Object
    Set h = CreateObject("MSXML2.ServerXMLHTTP.6.0")
    On Error GoTo SinConexion
    h.setTimeouts 10000, 10000, 120000, 300000
    h.Open metodo, url, False
    ' Correo + clave de acceso personal (la emite el sistema al descargar; vence y se anula al descargar de nuevo)
    h.setRequestHeader "Authorization", "Bearer " & Cfg(2)
    h.setRequestHeader "X-Correo", Cfg(6)
    h.setRequestHeader "Accept", "text/plain"
    If IsEmpty(cuerpo) Then
        h.send
    Else
        h.setRequestHeader "Content-Type", "application/octet-stream"
        h.send cuerpo
    End If
    On Error GoTo 0
    If h.Status = 200 Then
        Set Peticion = h
    Else
        MostrarError h
    End If
    Exit Function
SinConexion:
    MsgBox "No se pudo conectar con el sistema en " & Cfg(1) & "." & vbLf & "Revisa tu conexión o descarga el Excel de nuevo desde el sistema." & _
           vbLf & vbLf & "Más ayuda en la hoja ""Ayuda"".", vbExclamation, "Sistema"
End Function

' Errores del sistema: la respuesta es "ERROR:CODIGO" y en las líneas siguientes el mensaje para el usuario.
Private Sub MostrarError(h As Object)
    Dim texto As String, codigo As String, mensaje As String, p As Long
    texto = Replace(CStr(h.responseText), vbCr, "")
    If Left(texto, 6) = "ERROR:" Then
        p = InStr(texto, vbLf)
        If p = 0 Then p = Len(texto) + 1
        codigo = Mid(texto, 7, p - 7)
        mensaje = Mid(texto, p + 1)
    Else
        codigo = "HTTP " & h.Status
        mensaje = "El sistema respondió con un error inesperado." & vbLf & Left(texto, 400)
    End If
    MsgBox mensaje & vbLf & vbLf & "Código: " & codigo & " (detalle en la hoja ""Ayuda"").", vbExclamation, "Sistema"
End Sub
