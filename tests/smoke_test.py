import re, sys, requests
from datetime import date, timedelta
B = "http://127.0.0.1:8123"
fails = []
def check(name, cond, extra=""):
    print(("PASS " if cond else "FAIL ") + name + ((" -> " + extra) if (extra and not cond) else ""))
    if not cond: fails.append(name)
def csrf(html):
    m = re.search(r'name="csrf_token" value="([a-f0-9]+)"', html); return m.group(1)
def sess(): return requests.Session()
def login(s, correo, pw="demo1234"):
    h = s.get(B+"/login.php").text
    return s.post(B+"/login.php", data={"csrf_token": csrf(h), "accion": "login", "correo": correo, "password": pw})
def flash_text(html):
    m = re.search(r'role="status"[^>]*>\s*(.*?)\s*</div>', html, re.S); return re.sub(r'\s+',' ',m.group(1)) if m else ""
def next_dow(name):
    idx = {"Lunes":0,"Martes":1,"Miércoles":2,"Jueves":3,"Viernes":4,"Sábado":5,"Domingo":6}[name]
    d = date.today() + timedelta(days=1)
    while d.weekday() != idx: d += timedelta(days=1)
    return d.isoformat()

# --- Pages public
s = sess()
r = s.get(B+"/"); check("home 200", r.status_code==200)
check("home new copy", "Regulariza tus materias" in r.text and "Perfil Dual" not in r.text)
check("home 6 categorias escolares", all(c in r.text for c in ["Matemáticas","Física y Química","Biología","Español y Humanidades","Inglés e Idiomas","Programación y Tecnología"]))
check("home sin rol en tarjetas", "Rol:" not in r.text.split("Directorio")[-1])
check("home filtro nivel", 'name="nivel"' in r.text)
r = s.get(B+"/?nivel=Secundaria"); check("filtro nivel Secundaria (Mariana+Carlos)", "Mariana Soto" in r.text and "Carlos Mendoza" in r.text and "Diego Vázquez" not in r.text)
r = s.get(B+"/agenda.php", allow_redirects=False); check("agenda requiere login", r.status_code==302 and "login.php" in r.headers["Location"])
r = s.get(B+"/panel.php", allow_redirects=False); check("panel requiere login", r.status_code==302)
r = s.get(B+"/perfil.php?id=4"); check("perfil publico sin correo", "valeria.rios@" not in r.text and "Iniciar sesión" in r.text)
check("perfil sin 'Simular'", "Simular" not in r.text and "Revisión 1" not in r.text)

# --- Registro estudiante
st = sess()
h = st.get(B+"/login.php?modo=registro").text
check("registro form: rol, nivel, show/hide", 'data-rol-radio' in h and 'name="nivel_educativo"' in h and 'data-toggle-password' in h and 'name="ocupacion"' not in h)
r = st.post(B+"/login.php?modo=registro", data={"csrf_token":csrf(h),"accion":"registro","rol":"estudiante","nombre":"Ana Prueba Test","correo":"ana.test@example.com","password":"secreto1","nivel_educativo":"Preparatoria","modalidad":"Virtual","telefono":"55 1234 5678"})
check("registro estudiante ok -> index", r.status_code==200 and "Cuenta creada" in flash_text(r.text), flash_text(r.text))
# duplicate
st2 = sess(); h2 = st2.get(B+"/login.php?modo=registro").text
r = st2.post(B+"/login.php?modo=registro", data={"csrf_token":csrf(h2),"accion":"registro","rol":"estudiante","nombre":"Otra Persona","correo":"ana.test@example.com","password":"secreto1","nivel_educativo":"Secundaria"})
check("registro correo duplicado", "Ya existe una cuenta" in r.text)
r = st2.post(B+"/login.php?modo=registro", data={"csrf_token":csrf(h2),"accion":"registro","rol":"estudiante","nombre":"Otra Persona","correo":"x@example.com","password":"secreto1","nivel_educativo":"Marciano"})
check("registro nivel invalido", "nivel educativo" in r.text)
r = st2.post(B+"/login.php?modo=registro", data={"csrf_token":"bad","accion":"registro"})
check("registro csrf invalido", "caducó" in r.text)

# --- Solicitudes (Ana = estudiante) a Alejandro (id 1), bloque Lunes id 1
blk_dia = "Lunes"; fecha_ok = next_dow(blk_dia)
def solicitar(s, mentor, hab, disp, fecha, tema="Repaso de lógica de programación"):
    h = s.get(B+f"/perfil.php?id={mentor}").text
    return s.post(B+f"/perfil.php?id={mentor}", data={"csrf_token":csrf(h),"habilidad_id":hab,"disponibilidad_id":disp,"fecha_sesion":fecha,"tema_especifico":tema,"mensaje_estudiante":"Hola"})
r = solicitar(st, 1, 1, 1, (date.today()-timedelta(days=1)).isoformat()); check("rechaza fecha pasada", "anteriores a hoy" in r.text)
r = solicitar(st, 1, 1, 1, next_dow("Martes")); check("rechaza dia de semana distinto al bloque", "cae en Martes" in r.text, r.text[:0])
r = solicitar(st, 1, 3, 1, fecha_ok); check("rechaza materia de otro mentor", "no pertenece" in r.text)
r = solicitar(st, 1, 1, 1, fecha_ok, tema="ab"); check("rechaza tema corto", "entre 5 y 150" in r.text)
r = solicitar(st, 1, 1, 1, fecha_ok); check("solicitud OK -> agenda pendientes", "Solicitud enviada" in flash_text(r.text) and "Pendientes" in r.text, flash_text(r.text))
r = solicitar(st, 1, 1, 1, fecha_ok); check("rechaza duplicado activo", "solicitud activa" in r.text)

# Ana cancela / mentor-only no puede solicitar / self
ag = st.get(B+"/agenda.php?estado=pendientes").text
check("agenda estudiante: boton cancelar, sin aceptar", 'value="cancelar"' in ag and 'value="aceptar"' not in ag)

# --- Mentor Alejandro
m = sess(); r = login(m, "alejandro.morales@mentormatch.demo"); check("login mentor", "Mi Panel" in r.text)
pn = m.get(B+"/panel.php").text
check("panel: pendiente de Ana visible con aceptar", "Ana Prueba Test" in pn and 'value="aceptar"' in pn)
sol_id = re.search(r'name="solicitud_id" value="(\d+)"', pn[pn.index("Ana Prueba Test")-3000:]).group(1) if "Ana Prueba Test" in pn else None
# mentor no puede cancelar pendiente
r = m.post(B+"/accion_sesion.php", data={"csrf_token":csrf(pn),"accion":"cancelar","solicitud_id":sol_id,"volver":"panel.php"})
check("mentor no cancela pendiente (usa rechazar)", "usa" in flash_text(r.text) and "Rechazar" in flash_text(r.text), flash_text(r.text))
# otro mentor no puede aceptar
o = sess(); login(o, "valeria.rios@mentormatch.demo"); po = o.get(B+"/panel.php").text
r = o.post(B+"/accion_sesion.php", data={"csrf_token":csrf(po),"accion":"aceptar","solicitud_id":sol_id,"volver":"panel.php"})
check("otro mentor no puede aceptar ajena", "no encontrada" in flash_text(r.text), flash_text(r.text))
# estudiante no puede aceptar
pa = st.get(B+"/agenda.php").text
r = st.post(B+"/accion_sesion.php", data={"csrf_token":csrf(pa),"accion":"aceptar","solicitud_id":sol_id,"volver":"agenda.php"})
check("estudiante no puede aceptar", "no encontrada" in flash_text(r.text), flash_text(r.text))
# csrf malo
r = m.post(B+"/accion_sesion.php", data={"csrf_token":"x","accion":"aceptar","solicitud_id":sol_id,"volver":"panel.php"}); check("accion csrf invalido", "caducó" in flash_text(r.text))
# open redirect
r = m.post(B+"/accion_sesion.php", data={"csrf_token":csrf(pn),"accion":"zzz","solicitud_id":sol_id,"volver":"https://evil.com"}, allow_redirects=False)
check("volver externo se ignora", r.headers.get("Location")=="agenda.php", r.headers.get("Location",""))
# aceptar
r = m.post(B+"/accion_sesion.php", data={"csrf_token":csrf(pn),"accion":"aceptar","solicitud_id":sol_id,"volver":"panel.php"})
check("mentor acepta", "aceptada" in flash_text(r.text).lower(), flash_text(r.text))
r = m.post(B+"/accion_sesion.php", data={"csrf_token":csrf(pn),"accion":"aceptar","solicitud_id":sol_id,"volver":"panel.php"})
check("no se puede aceptar dos veces", "ya fue respondida" in flash_text(r.text), flash_text(r.text))
# Nuevo estudiante intenta mismo bloque/fecha -> conflicto
s3 = sess(); h3 = s3.get(B+"/login.php?modo=registro").text
s3.post(B+"/login.php?modo=registro", data={"csrf_token":csrf(h3),"accion":"registro","rol":"ambos","nombre":"Beto Ambos Test","correo":"beto.test@example.com","password":"secreto1","nivel_educativo":"Universidad","anios_experiencia":"2"})
r = solicitar(s3, 1, 1, 1, fecha_ok); check("conflicto con sesion aceptada", "ya tiene una sesión aceptada" in r.text)
# Student ve telefono del mentor? (mentor demo sin telefono) -> ana ve sesion proxima
ag = st.get(B+"/agenda.php?estado=proximas").text; check("agenda estudiante: proximas con cancelar", "ACEPTADA" in ag.upper() and 'value="cancelar"' in ag)
# no se puede completar sesion futura
pa2 = m.get(B+"/agenda.php?estado=proximas").text
check("'Marcar completada' solo en la sesion pasada (seed), no en la futura", pa2.count("Marcar completada") == 1)
r = m.post(B+"/accion_sesion.php", data={"csrf_token":csrf(pa2),"accion":"completar","solicitud_id":sol_id,"volver":"agenda.php"}); check("completar futura rechazado", "aún no ha iniciado" in flash_text(r.text), flash_text(r.text))
# Ana cancela sesion aceptada -> horario libre -> Beto puede solicitar
r = st.post(B+"/accion_sesion.php", data={"csrf_token":csrf(ag),"accion":"cancelar","solicitud_id":sol_id,"volver":"agenda.php?estado=proximas"})
check("estudiante cancela aceptada", "cancelada" in flash_text(r.text).lower(), flash_text(r.text))
r = solicitar(s3, 1, 1, 1, fecha_ok, "Dudas de arreglos"); check("horario liberado tras cancelar", "Solicitud enviada" in flash_text(r.text), flash_text(r.text))

# auto-rechazo: dos pendientes mismo bloque, al aceptar una se rechaza la otra
st_b = sess(); hb = st_b.get(B+"/login.php?modo=registro").text
st_b.post(B+"/login.php?modo=registro", data={"csrf_token":csrf(hb),"accion":"registro","rol":"estudiante","nombre":"Cris Estudiante","correo":"cris.test@example.com","password":"secreto1","nivel_educativo":"Secundaria"})
r = solicitar(st_b, 1, 1, 1, fecha_ok, "Tema de Cris para clase"); check("2da pendiente mismo bloque permitida (de otro alumno)", "Solicitud enviada" in flash_text(r.text), flash_text(r.text))
pn = m.get(B+"/panel.php").text
ids = re.findall(r'name="solicitud_id" value="(\d+)"', pn); first = ids[0]
r = m.post(B+"/accion_sesion.php", data={"csrf_token":csrf(pn),"accion":"aceptar","solicitud_id":first,"volver":"panel.php"})
check("aceptar rechaza auto las que chocan", "automáticamente" in flash_text(r.text), flash_text(r.text))

# mentor-only no puede solicitar
mo = sess(); login(mo, "diego.vazquez@mentormatch.demo")
h = mo.get(B+"/perfil.php?id=4").text; check("mentor-only ve aviso en perfil", "no puedes solicitar" in h)
r = mo.post(B+"/perfil.php?id=4", data={"csrf_token":csrf(mo.get(B+"/panel.php").text),"habilidad_id":6,"disponibilidad_id":8,"fecha_sesion":next_dow("Martes"),"tema_especifico":"Tema de prueba"})
check("mentor-only POST bloqueado en servidor", "tipo Mentor" in r.text)
# self
h = m.get(B+"/perfil.php?id=1").text; check("mi propio perfil: sin formulario", "Editar en Mi Panel" in h and 'id="form-solicitud"' not in h)

# completar: sesion seed id1 (23-nov) de Alejandro con Luis
m_ag = m.get(B+"/agenda.php?estado=todas").text
seed_sol = re.search(r'Programación desde Cero.*?name="solicitud_id" value="(\d+)"', m_ag, re.S)
check("mentor ve 'Marcar completada' (sesion pasada)", "Marcar completada" in m_ag)
r = m.post(B+"/accion_sesion.php", data={"csrf_token":csrf(m_ag),"accion":"completar","solicitud_id":"1","notas_cierre":"Se vieron arreglos","volver":"agenda.php?estado=todas"})
check("mentor completa sesion pasada", "completada" in flash_text(r.text).lower(), flash_text(r.text))
r = m.get(B+"/agenda.php?estado=historial"); check("completada en historial con notas", "COMPLETADA" in r.text.upper() and "Se vieron arreglos" in r.text)
r = m.post(B+"/accion_sesion.php", data={"csrf_token":csrf(m_ag),"accion":"completar","solicitud_id":"1","volver":"agenda.php"}); check("no completar dos veces", "Solo se pueden completar" in flash_text(r.text))

# --- Reseñas (PAQ-10)
pg = s.get(B+"/perfil.php?id=1").text
check("seed: perfil con seccion de resenas verificadas", 'id="resenas"' in pg and "Sesión verificada" in pg and "Luis F." not in pg and "Luis G." in pg)
check("seed: reseña solo con estrellas se muestra", "Solo dejó calificación, sin comentario" in pg)
check("seed: reseña solo con comentario (sin estrellas)", "Domina el tema y tiene mucha paciencia" in s.get(B+"/perfil.php?id=3").text)
check("seed: promedio calculado de resenas (Alejandro 4.7, 3 calificaciones)", "4.7" in pg and "3 calificaciones" in pg)
check("catalogo muestra promedio y conteo", "★ 4.7 (3)" in s.get(B+"/").text)

lu = sess(); login(lu, "luis.gomez@mentormatch.demo")
ag_lu = lu.get(B+"/agenda.php").text
check("estudiante ve aviso de sesion sin reseña", "1 sesión completada sin reseña" in ag_lu)
his = lu.get(B+"/agenda.php?estado=historial").text
check("historial: boton 'Dejar reseña (opcional)' con selector de estrellas", "Dejar reseña (opcional)" in his and 'name="calificacion"' in his and 'value="resenar"' in his)
tok_lu = csrf(his)
def resenar_lu(solicitud, cal="", comentario=""):
    return lu.post(B+"/accion_sesion.php", data={"csrf_token":tok_lu,"accion":"resenar","solicitud_id":solicitud,"calificacion":cal,"comentario":comentario,"volver":"agenda.php?estado=historial"})
r = resenar_lu("1"); check("resena vacia rechazada (es opcional pero debe traer algo)", "Elige una calificación o escribe un comentario" in flash_text(r.text), flash_text(r.text))
r = resenar_lu("1", "6", "x"); check("estrellas fuera de 1-5 rechazadas", "1 a 5" in flash_text(r.text), flash_text(r.text))
r = resenar_lu("1", "abc", "x"); check("estrellas no numericas rechazadas", "1 a 5" in flash_text(r.text), flash_text(r.text))
r = resenar_lu("1", "5", "y"*501); check("comentario >500 rechazado", "500" in flash_text(r.text), flash_text(r.text))
r = resenar_lu("2", "5", "Intento sobre una solicitud pendiente"); check("no se reseña una solicitud sin sesion completada", "no encontrada" in flash_text(r.text).lower() or "completadas" in flash_text(r.text), flash_text(r.text))
# sesion aceptada pero aun programada (de Beto o Cris)
prog = None
for acct in (s3, st_b):
    pa_ = acct.get(B+"/agenda.php?estado=proximas").text
    mm_ = re.search(r'value="cancelar">\s*<input type="hidden" name="solicitud_id" value="(\d+)"', pa_)
    if mm_: prog = (acct, mm_.group(1), csrf(pa_)); break
check("hay una sesion programada para probar", prog is not None)
if prog:
    r = prog[0].post(B+"/accion_sesion.php", data={"csrf_token":prog[2],"accion":"resenar","solicitud_id":prog[1],"calificacion":"5","comentario":"Adelantado","volver":"agenda.php"})
    check("no se reseña una sesion solo programada", "marcó como completadas" in flash_text(r.text), flash_text(r.text))
# ajenas
r = st.post(B+"/accion_sesion.php", data={"csrf_token":csrf(st.get(B+"/perfil.php?id=4").text),"accion":"resenar","solicitud_id":"1","calificacion":"1","comentario":"Sabotaje","volver":"agenda.php"}); check("otro estudiante no puede reseñar sesion ajena", "propias sesiones" in flash_text(r.text), flash_text(r.text))
r = m.post(B+"/accion_sesion.php", data={"csrf_token":csrf(m.get(B+"/panel.php").text),"accion":"resenar","solicitud_id":"1","calificacion":"5","comentario":"Autoreseña","volver":"panel.php"}); check("el mentor no puede reseñarse a si mismo", "propias sesiones" in flash_text(r.text), flash_text(r.text))
# valida
r = resenar_lu("1", "5", "Muy buena clase, entendí arreglos y funciones.")
check("estudiante publica reseña (estrellas + comentario)", "ya aparece en el perfil" in flash_text(r.text), flash_text(r.text))
r = resenar_lu("1", "1", "Segundo intento"); check("una sola reseña por sesion", "Ya dejaste una reseña" in flash_text(r.text), flash_text(r.text))
pg = s.get(B+"/perfil.php?id=1").text
check("la reseña nueva sale en el perfil del mentor con nombre abreviado", "Muy buena clase, entendí arreglos y funciones." in pg and "Luis G." in pg and "Luis Fernando" not in pg)
check("promedio se recalcula (5,5,4,5 -> 4.8; 4 calificaciones)", "4.8" in pg and "4 calificaciones" in pg)
check("catalogo actualizado ★ 4.8 (4)", "★ 4.8 (4)" in s.get(B+"/").text)
his = lu.get(B+"/agenda.php?estado=historial").text
check("agenda: muestra 'Tu reseña' y ya no ofrece reseñar esa sesion", "Tu reseña" in his and "Muy buena clase" in his and "1 sesión completada sin reseña" not in lu.get(B+"/agenda.php").text)
pn_m = m.get(B+"/panel.php").text
check("panel del mentor muestra la reseña recibida", "Reseñas que he recibido" in pn_m and "Muy buena clase, entendí" in pn_m and "Sesión verificada" in pn_m)
check("mentor ve la reseña del estudiante en su agenda", "Reseña del estudiante" in m.get(B+"/agenda.php?estado=historial").text)

# --- Panel: CRUD horarios/materias/perfil
pn = m.get(B+"/panel.php").text
def panel(data): 
    d = {"csrf_token": csrf(m.get(B+"/panel.php").text)}; d.update(data); return m.post(B+"/panel.php", data=d)
r = panel({"accion":"agregar_horario","dia_semana":"Lunes","hora_inicio":"17:30","hora_fin":"18:30","modalidad":"Virtual","lugar_o_enlace":""}); check("horario traslapado rechazado", "traslapa" in flash_text(r.text), flash_text(r.text))
r = panel({"accion":"agregar_horario","dia_semana":"Sábado","hora_inicio":"18:00","hora_fin":"17:00","modalidad":"Virtual"}); check("hora fin <= inicio rechazado", "posterior" in flash_text(r.text))
r = panel({"accion":"agregar_horario","dia_semana":"Sábado","hora_inicio":"09:00","hora_fin":"10:00","modalidad":"Presencial","lugar_o_enlace":""}); check("presencial exige lugar", "lugar" in flash_text(r.text))
r = panel({"accion":"agregar_horario","dia_semana":"Sábado","hora_inicio":"09:00","hora_fin":"10:00","modalidad":"Virtual","lugar_o_enlace":"Meet"}); check("agregar horario ok", "Horario publicado" in flash_text(r.text), flash_text(r.text))
r = panel({"accion":"agregar_materia","materia":"Geometría Básica","area":"Matemáticas","nivel":"Secundaria","descripcion_tema":"Figuras, áreas y volúmenes para secundaria."}); check("agregar materia ok", "Materia publicada" in flash_text(r.text), flash_text(r.text))
r = panel({"accion":"agregar_materia","materia":"geometría básica","area":"Matemáticas","nivel":"Secundaria","descripcion_tema":"Figuras, áreas y volúmenes para secundaria."}); check("materia duplicada rechazada", "Ya publicaste" in flash_text(r.text))
r = panel({"accion":"agregar_materia","materia":"Magia","area":"Hechicería","nivel":"Secundaria","descripcion_tema":"Cosas de magia muy avanzadas."}); check("categoria invalida rechazada", "categoría válida" in flash_text(r.text))
r = panel({"accion":"eliminar_materia","habilidad_id":"1"}); check("materia con historial no se elimina", "historial" in flash_text(r.text), flash_text(r.text))
newhab = re.search(r'name="habilidad_id" value="(\d+)"[^>]*>\s*<button', m.get(B+"/panel.php").text)
ph = m.get(B+"/panel.php").text
hid = [x for x in re.findall(r'accion" value="eliminar_materia">\s*<input type="hidden" name="habilidad_id" value="(\d+)"', ph)]
check("hay materias eliminables", len(hid)>=3)
geo = re.search(r'Geometría Básica.*?name="habilidad_id" value="(\d+)"', ph, re.S).group(1)
r = panel({"accion":"eliminar_materia","habilidad_id":geo}); check("elimina materia sin historial (Geometría)", "Materia eliminada" in flash_text(r.text), flash_text(r.text))
did = re.findall(r'accion" value="eliminar_horario">\s*<input type="hidden" name="disponibilidad_id" value="(\d+)"', m.get(B+"/panel.php").text)
r = panel({"accion":"eliminar_horario","disponibilidad_id":did[0]}); check("eliminar horario (baja logica)", "Horario eliminado" in flash_text(r.text))
r = panel({"accion":"guardar_perfil","ocupacion":"Tutor","biografia":"x"*601,"anios_experiencia":"5","tarifa_hora":"100","modalidad":"Ambas","ubicacion":"CDMX","telefono":"55 5555 5555"}); check("bio >600 rechazada", "600" in flash_text(r.text))
r = panel({"accion":"guardar_perfil","ocupacion":"Tutor de Mate y Código","biografia":"Doy clases claras.","anios_experiencia":"6","tarifa_hora":"175","modalidad":"Ambas","ubicacion":"CDMX","telefono":"55 5555 5555"}); check("guardar perfil ok", "Perfil actualizado" in flash_text(r.text))
r = s.get(B+"/"); check("perfil nuevo en catalogo ($175 / hr, 6 años)", "$175 / hr" in r.text and "6 años de experiencia" in r.text)
# estudiante no accede al panel
r = st.get(B+"/panel.php", allow_redirects=True); check("estudiante no entra a panel", "solo para mentores" in flash_text(r.text), flash_text(r.text))
# nav
check("nav estudiante sin Mi Panel", "Mi Panel" not in st.get(B+"/").text)

# --- Nuevo mentor sin materias no aparece; aparece al completar
mm = sess(); hh = mm.get(B+"/login.php?modo=registro").text
r = mm.post(B+"/login.php?modo=registro", data={"csrf_token":csrf(hh),"accion":"registro","rol":"mentor","nombre":"Nuevo Mentor Test","correo":"nuevo.mentor@example.com","password":"secreto1","nivel_educativo":"Universidad","anios_experiencia":"0","modalidad":"Ambas","ubicacion":"Toluca"})
check("registro mentor -> panel", "Completa tu perfil" in r.text and "Mi Panel de Mentor" in r.text)
check("nuevo mentor oculto del catalogo", "Nuevo Mentor Test" not in s.get(B+"/").text)
dm = {"csrf_token": csrf(mm.get(B+"/panel.php").text)}
mm.post(B+"/panel.php", data={**dm,"accion":"agregar_materia","materia":"Química General","area":"Física y Química","nivel":"Preparatoria","descripcion_tema":"Estequiometría, enlaces y reacciones."})
mm.post(B+"/panel.php", data={**dm,"accion":"agregar_horario","dia_semana":"Viernes","hora_inicio":"16:00","hora_fin":"17:00","modalidad":"Virtual","lugar_o_enlace":""})
cat = s.get(B+"/").text; check("nuevo mentor aparece con 'Nuevo' y Gratis", "Nuevo Mentor Test" in cat and ">\n                                    Nuevo\n" in cat.replace("\r","") or ("Nuevo Mentor Test" in cat and "Gratis" in cat))

# =====================================================================
# v3: cambios de la ronda 3
# =====================================================================
# 1) Registro: "Otro" permite escribir el grado
hh = sess().get(B+"/login.php?modo=registro").text
check("v3 registro: barra para 'Otro' presente", 'id="reg-nivel-otro"' in hh and 'name="nivel_otro"' in hh)
ot = sess(); h = ot.get(B+"/login.php?modo=registro").text
r = ot.post(B+"/login.php?modo=registro", data={"csrf_token":csrf(h),"accion":"registro","rol":"estudiante","nombre":"Bruno Otro Test","correo":"bruno.test@example.com","password":"secreto1","nivel_educativo":"Otro","nivel_otro":"","modalidad":"Virtual"})
check("v3 'Otro' sin texto se rechaza", "Escribe cuál es tu grado" in r.text)
h = ot.get(B+"/login.php?modo=registro").text
r = ot.post(B+"/login.php?modo=registro", data={"csrf_token":csrf(h),"accion":"registro","rol":"estudiante","nombre":"Bruno Otro Test","correo":"bruno.test@example.com","password":"secreto1","nivel_educativo":"Otro","nivel_otro":"Maestría","modalidad":"Virtual"})
check("v3 'Otro' con texto crea cuenta", "Cuenta creada" in flash_text(r.text), flash_text(r.text))

# 2) Catálogo: filtro por categoría, sin 'Texto:' ni 'Perfil Completo'
cat = s.get(B+"/").text
check("v3 filtro por categoría (select) y sin filtro de materia", 'id="select-area"' in cat and 'id="select-materia"' not in cat)
check("v3 select de categoría lista las 6 categorías", cat.count('<option value="Matemáticas"') == 1 and cat.count('<option value="Programación y Tecnología"') == 1)
check("v3 tarjetas sin 'Perfil Completo'", "Perfil Completo" not in cat.split('id="grid-mentores"')[-1])
r = s.get(B+"/?q=algebra"); check("v3 filtro activo sin 'Texto:'", "Filtros activos" in r.text and "Texto:" not in r.text and '>"algebra"<' in r.text)
r = s.get(B+"/?area=Matem%C3%A1ticas"); check("v3 filtro por categoría funciona", "Alejandro Morales" in r.text and "Carlos Mendoza" not in r.text)

# 3) Agenda: el rol mentor no ve 'Buscar un mentor'
dg = sess(); login(dg, "diego.vazquez@mentormatch.demo")
ag = dg.get(B+"/agenda.php").text
check("v3 agenda de mentor sin 'Buscar un mentor'", "Buscar un mentor" not in ag and "Mi Agenda" in ag)
check("v3 agenda de estudiante conserva 'Buscar un mentor'", "Buscar un mentor" in ot.get(B+"/agenda.php").text)
ambos = sess(); login(ambos, "alejandro.morales@mentormatch.demo")
check("v3 agenda de rol 'ambos' conserva 'Buscar un mentor'", "Buscar un mentor" in ambos.get(B+"/agenda.php").text)

# 4) Panel: sin autocompletado, aviso de gratuita, 'Preferencia'
pm = mm.get(B+"/panel.php").text
check("v3 panel: titular vacío (sin 'Mentor de regularización')", "Mentor de regularización" not in pm and re.search(r'name="ocupacion"[^>]*value=""', pm) is not None)
check("v3 panel: sin '0 = gratis' y con aviso de asesoría gratuita", "0 = gratis" not in pm and "quedarán asignadas como" in pm and "gratuitas" in pm)
check("v3 panel: tarifa vacía y 'Preferencia' en lugar de 'Modalidad'", re.search(r'name="tarifa_hora"[^>]*value=""', pm) is not None and ">Preferencia</label>" in pm)

# 5) Presencial solo en la misma ciudad (Alejandro está en Ciudad de México; bloque 2 = Miércoles presencial)
miercoles = next_dow("Miércoles")
r = solicitar(ot, 1, 1, 2, miercoles)
check("v3 presencial sin ciudad registrada se rechaza", "registra tu ciudad" in r.text, r.text[:0])
h = ot.get(B+"/perfil.php?id=1").text
check("v3 perfil: bloque presencial deshabilitado y formulario de ciudad", 'disabled' in h and 'name="accion" value="guardar_ciudad"' in h)
r = ot.post(B+"/accion_sesion.php", data={"csrf_token":csrf(h),"accion":"guardar_ciudad","ubicacion":"Guadalajara","volver":"index.php?modalidad=Presencial"})
check("v3 guardar ciudad (Guadalajara)", "Ciudad guardada" in flash_text(r.text), flash_text(r.text))
r = solicitar(ot, 1, 1, 2, miercoles)
check("v3 presencial en otra ciudad se rechaza", "solo se agendan con mentores de tu ciudad" in r.text)
r = ot.get(B+"/?modalidad=Presencial")
check("v3 catálogo presencial: solo mentores de Guadalajara", "Diego Vázquez" in r.text and "Alejandro Morales" not in r.text and "En tu ciudad" in r.text)
r = s.get(B+"/?modalidad=Presencial"); check("v3 catálogo presencial sin sesión muestra a todos", "Alejandro Morales" in r.text and "Diego Vázquez" in r.text)
h = ot.get(B+"/perfil.php?id=1").text
r = ot.post(B+"/accion_sesion.php", data={"csrf_token":csrf(h),"accion":"guardar_ciudad","ubicacion":"cdmx","volver":"agenda.php"})
r = solicitar(ot, 1, 1, 2, miercoles)
check("v3 presencial en la misma ciudad (sin importar mayúsculas) se acepta", "Solicitud enviada" in flash_text(r.text), flash_text(r.text))
check("v3 ciudad 'Online' no es válida", "nombre de tu ciudad" in ot.post(B+"/accion_sesion.php", data={"csrf_token":csrf(h),"accion":"guardar_ciudad","ubicacion":"Remoto / Online","volver":"agenda.php"}, allow_redirects=True).text)

# 6) Logística: nunca dos sesiones aceptadas al mismo tiempo
#    Mariana (id 2) tiene Martes 19:00-20:00 virtual; el mentor nuevo publica el mismo horario.
mar = (date.fromisoformat(next_dow("Martes")) + timedelta(days=14)).isoformat()
nm_panel = mm.get(B+"/panel.php").text
mm.post(B+"/panel.php", data={"csrf_token":csrf(nm_panel),"accion":"agregar_horario","dia_semana":"Martes","hora_inicio":"19:00","hora_fin":"20:00","modalidad":"Virtual","lugar_o_enlace":"Meet"})
nm_id = re.search(r'perfil\.php\?id=(\d+)', mm.get(B+"/panel.php").text).group(1)
est = sess(); login(est, "luis.gomez@mentormatch.demo")
def ids_perfil(sesion, mentor, dia):
    h = sesion.get(B+f"/perfil.php?id={mentor}").text
    disp = re.findall(r'<option\s+value="(\d+)"\s+data-dia="%s"' % dia, h)[0]
    hab = re.search(r'name="habilidad_id"[^>]*>\s*<option value="(\d+)"', h).group(1)
    return hab, disp
hM, dM = ids_perfil(est, 2, "Martes"); hN, dN = ids_perfil(est, nm_id, "Martes")
r1 = solicitar(est, 2, hM, dM, mar, tema="Choque de horarios uno")
r2 = solicitar(est, nm_id, hN, dN, mar, tema="Choque de horarios dos")
check("v3 dos solicitudes pendientes al mismo tiempo se pueden enviar", "Solicitud enviada" in flash_text(r1.text) and "Solicitud enviada" in flash_text(r2.text), flash_text(r1.text)+"|"+flash_text(r2.text))
r3 = solicitar(ot, 2, hM, dM, mar, tema="Mismo horario otro alumno")
check("v3 otro alumno también puede pedir el mismo bloque mientras esté pendiente", "Solicitud enviada" in flash_text(r3.text), flash_text(r3.text))
mar_s = sess(); login(mar_s, "mariana.soto@mentormatch.demo"); pa = mar_s.get(B+"/agenda.php?estado=pendientes").text
def sid_de(html, nombre):
    if nombre not in html: return None
    m_ = re.search(r'name="solicitud_id" value="(\d+)"', html[html.index(nombre):])
    return m_.group(1) if m_ else None
sid1 = sid_de(pa, "Luis Fernando")
check("v3 mentora ve la solicitud para aceptar", sid1 is not None)
r = mar_s.post(B+"/accion_sesion.php", data={"csrf_token":csrf(pa),"accion":"aceptar","solicitud_id":sid1,"volver":"agenda.php"})
check("v3 se acepta la primera sesión", "Solicitud aceptada" in flash_text(r.text), flash_text(r.text))
check("v3 al aceptar se rechazan las pendientes que chocan", "rechazaron automáticamente" in flash_text(r.text) and "2 solicitud" in flash_text(r.text), flash_text(r.text))
pa2 = mar_s.get(B+"/agenda.php?estado=pendientes").text
check("v3 la mentora ya no tiene pendientes en ese horario", "Mismo horario otro alumno" not in pa2)
ag_est = est.get(B+"/agenda.php?estado=pendientes").text
check("v3 la solicitud del mismo alumno a otro mentor a la misma hora se rechazó sola", "Choque de horarios dos" not in ag_est)
r = solicitar(est, nm_id, hN, dN, mar, tema="Otro intento mismo horario")
check("v3 el alumno no puede pedir otra sesión a la misma hora que una aceptada", "Ya tienes una sesión aceptada" in r.text)
r = solicitar(ot, 2, hM, dM, mar, tema="Un tercero intenta ese bloque")
check("v3 nadie más puede pedir un bloque ya aceptado del mentor", "El mentor ya tiene una sesión aceptada" in r.text)

print("\nFAILS:", fails if fails else "none"); sys.exit(1 if fails else 0)
