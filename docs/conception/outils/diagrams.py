"""Génère les diagrammes de conception en .drawio (modifiable), .svg et .html (pour le PNG).

Usage : python3 diagrams.py <dossier de sortie>
Chaque diagramme est décrit une fois ; le .drawio et le .svg partagent la même géométrie.
"""
import html
import math
import sys
from pathlib import Path

OUT = Path(sys.argv[1] if len(sys.argv) > 1 else '.')
FONT = 'Segoe UI, Roboto, Helvetica, Arial, sans-serif'
LINE = 16
PRIMARY = '#8b2635'
INK = '#2c3e50'
SOFT = '#faf7f4'
MONGO = '#e9f6ef'
SERVICE = '#eef3fb'


def esc(text):
    return html.escape(str(text), quote=True)


class Diagram:
    def __init__(self, name, title, width, height):
        self.name, self.title, self.w, self.h = name, title, width, height
        self.cells, self.svg, self.shapes = [], [], {}
        self.next_id = 2

    def _id(self):
        self.next_id += 1
        return f'c{self.next_id}'

    # ---------- formes ----------
    def box(self, key, x, y, w, title, sections=(), fill=SOFT, stereotype=None, title_fill=PRIMARY):
        lines = sum(len(s) for s in sections)
        h = 30 + (LINE if stereotype else 0) + lines * LINE + len(sections) * 10
        cid = self._id()
        self.shapes[key] = (cid, x, y, w, h)
        label = (f'<i>&laquo;{esc(stereotype)}&raquo;</i><br>' if stereotype else '') + f'<b>{esc(title)}</b>'
        for sec in sections:
            label += '<hr>' + '<br>'.join(esc(l) for l in sec)
        style = (f'rounded=0;whiteSpace=wrap;html=1;align=left;verticalAlign=top;spacingLeft=6;'
                 f'fillColor={fill};strokeColor={title_fill};fontSize=11;fontFamily=Segoe UI;')
        self.cells.append(f'<mxCell id="{cid}" value="{esc(label)}" style="{style}" vertex="1" parent="1">'
                          f'<mxGeometry x="{x}" y="{y}" width="{w}" height="{h}" as="geometry"/></mxCell>')
        s = [f'<rect x="{x}" y="{y}" width="{w}" height="{h}" fill="{fill}" stroke="{title_fill}" stroke-width="1.5"/>']
        head = 26 + (LINE if stereotype else 0)
        s.append(f'<rect x="{x}" y="{y}" width="{w}" height="{head}" fill="{title_fill}"/>')
        ty = y + 17
        if stereotype:
            s.append(f'<text x="{x + w / 2}" y="{ty}" text-anchor="middle" font-size="11" font-style="italic" fill="#fff">&#171;{esc(stereotype)}&#187;</text>')
            ty += LINE
        s.append(f'<text x="{x + w / 2}" y="{ty}" text-anchor="middle" font-size="13" font-weight="700" fill="#fff">{esc(title)}</text>')
        cy = y + head
        for sec in sections:
            cy += 4
            for line in sec:
                cy += LINE
                weight = ' font-weight="700"' if line.startswith('🔑') else ''
                s.append(f'<text x="{x + 7}" y="{cy - 4}" font-size="11.5" fill="{INK}"{weight}>{esc(line)}</text>')
            cy += 6
            s.append(f'<line x1="{x}" y1="{cy}" x2="{x + w}" y2="{cy}" stroke="{title_fill}" stroke-width="0.8"/>')
        self.svg.append(''.join(s[:-1]) if sections else ''.join(s))
        return h

    def ellipse(self, key, cx, cy, w, h, text, fill='#fff', stroke=PRIMARY, dashed=False):
        cid = self._id()
        x, y = cx - w / 2, cy - h / 2
        self.shapes[key] = (cid, x, y, w, h)
        style = f'ellipse;whiteSpace=wrap;html=1;fillColor={fill};strokeColor={stroke};fontSize=11;fontFamily=Segoe UI;' + ('dashed=1;' if dashed else '')
        self.cells.append(f'<mxCell id="{cid}" value="{esc(text)}" style="{style}" vertex="1" parent="1">'
                          f'<mxGeometry x="{x}" y="{y}" width="{w}" height="{h}" as="geometry"/></mxCell>')
        dash = ' stroke-dasharray="5 4"' if dashed else ''
        parts = text.split('\n')
        s = [f'<ellipse cx="{cx}" cy="{cy}" rx="{w / 2}" ry="{h / 2}" fill="{fill}" stroke="{stroke}" stroke-width="1.4"{dash}/>']
        for i, p in enumerate(parts):
            yy = cy + (i - (len(parts) - 1) / 2) * 14 + 4
            s.append(f'<text x="{cx}" y="{yy}" text-anchor="middle" font-size="11.5" fill="{INK}">{esc(p)}</text>')
        self.svg.append(''.join(s))

    def rect(self, x, y, w, h, label='', fill='none', stroke=INK, dashed=False, label_pos='top', key=None, rounded=False, font=13):
        cid = self._id()
        if key:
            self.shapes[key] = (cid, x, y, w, h)
        va = 'top' if label_pos == 'top' else 'middle'
        style = (f'rounded={1 if rounded else 0};whiteSpace=wrap;html=1;verticalAlign={va};fillColor={fill if fill != "none" else "none"};'
                 f'strokeColor={stroke};fontStyle=1;fontSize={font};fontFamily=Segoe UI;' + ('dashed=1;' if dashed else ''))
        self.cells.append(f'<mxCell id="{cid}" value="{esc(label)}" style="{style}" vertex="1" parent="1">'
                          f'<mxGeometry x="{x}" y="{y}" width="{w}" height="{h}" as="geometry"/></mxCell>')
        dash = ' stroke-dasharray="6 4"' if dashed else ''
        r = ' rx="8"' if rounded else ''
        s = f'<rect x="{x}" y="{y}" width="{w}" height="{h}" fill="{fill}" stroke="{stroke}" stroke-width="1.4"{dash}{r}/>'
        if label:
            parts = label.split('\n')
            for i, p in enumerate(parts):
                ty = (y + 20 + i * 16) if label_pos == 'top' else (y + h / 2 + (i - (len(parts) - 1) / 2) * 16 + 5)
                s += f'<text x="{x + w / 2}" y="{ty}" text-anchor="middle" font-size="{font}" font-weight="700" fill="{stroke}">{esc(p)}</text>'
        self.svg.append(s)

    def text(self, x, y, value, size=12, color=INK, anchor='start', bold=False, italic=False):
        cid = self._id()
        align = {'start': 'left', 'middle': 'center', 'end': 'right'}[anchor]
        w = max(40, len(value) * size * 0.6)
        gx = x if anchor == 'start' else (x - w / 2 if anchor == 'middle' else x - w)
        style = f'text;html=1;align={align};verticalAlign=middle;fontSize={size};fontColor={color};fontFamily=Segoe UI;' + ('fontStyle=1;' if bold else '') + ('fontStyle=2;' if italic else '')
        self.cells.append(f'<mxCell id="{cid}" value="{esc(value)}" style="{style}" vertex="1" parent="1">'
                          f'<mxGeometry x="{gx}" y="{y - size}" width="{w}" height="{size + 6}" as="geometry"/></mxCell>')
        self.svg.append(f'<text x="{x}" y="{y}" text-anchor="{anchor}" font-size="{size}" fill="{color}"'
                        f'{" font-weight=\"700\"" if bold else ""}{" font-style=\"italic\"" if italic else ""}>{esc(value)}</text>')

    def actor(self, key, cx, y, name):
        cid = self._id()
        self.shapes[key] = (cid, cx - 30, y, 60, 96)
        self.cells.append(f'<mxCell id="{cid}" value="{esc(name)}" style="shape=umlActor;verticalLabelPosition=bottom;verticalAlign=top;html=1;fontSize=12;fontStyle=1;fontFamily=Segoe UI;strokeColor={INK};" vertex="1" parent="1">'
                          f'<mxGeometry x="{cx - 18}" y="{y}" width="36" height="70" as="geometry"/></mxCell>')
        self.svg.append(
            f'<g stroke="{INK}" stroke-width="2" fill="none"><circle cx="{cx}" cy="{y + 10}" r="10" fill="#fff"/>'
            f'<line x1="{cx}" y1="{y + 20}" x2="{cx}" y2="{y + 45}"/><line x1="{cx - 16}" y1="{y + 30}" x2="{cx + 16}" y2="{y + 30}"/>'
            f'<line x1="{cx}" y1="{y + 45}" x2="{cx - 14}" y2="{y + 68}"/><line x1="{cx}" y1="{y + 45}" x2="{cx + 14}" y2="{y + 68}"/></g>'
            f'<text x="{cx}" y="{y + 86}" text-anchor="middle" font-size="12.5" font-weight="700" fill="{INK}">{esc(name)}</text>')

    # ---------- liens ----------
    @staticmethod
    def _border(shape, tx, ty):
        _, x, y, w, h = shape
        cx, cy = x + w / 2, y + h / 2
        dx, dy = tx - cx, ty - cy
        if dx == 0 and dy == 0:
            return cx, cy
        scale = min((w / 2) / abs(dx) if dx else math.inf, (h / 2) / abs(dy) if dy else math.inf)
        return cx + dx * scale, cy + dy * scale

    def edge(self, a, b, label='', a_label='', b_label='', dashed=False, arrow=None, head=None, via=None):
        sa, sb = self.shapes[a], self.shapes[b]
        pts = via or []
        first = pts[0] if pts else (sb[1] + sb[3] / 2, sb[2] + sb[4] / 2)
        last = pts[-1] if pts else (sa[1] + sa[3] / 2, sa[2] + sa[4] / 2)
        x1, y1 = self._border(sa, *first)
        x2, y2 = self._border(sb, *last)
        cid = self._id()
        end = {'open': 'endArrow=open;', 'triangle': 'endArrow=block;endFill=0;', 'diamond': 'endArrow=diamondThin;endFill=1;', None: 'endArrow=none;'}[arrow]
        start = {'diamond': 'startArrow=diamondThin;startFill=1;', None: ''}[head]
        style = f'html=1;rounded=0;{end}{start}strokeColor={INK};fontSize=11;fontFamily=Segoe UI;' + ('dashed=1;' if dashed else '')
        pts_xml = ''.join(f'<mxPoint x="{px}" y="{py}"/>' for px, py in pts)
        self.cells.append(f'<mxCell id="{cid}" value="{esc(label)}" style="{style}" edge="1" parent="1" source="{sa[0]}" target="{sb[0]}">'
                          f'<mxGeometry relative="1" as="geometry">{"<Array as=\"points\">" + pts_xml + "</Array>" if pts else ""}</mxGeometry></mxCell>')
        for lbl, pos in ((a_label, 0.12), (b_label, 0.88)):
            if lbl:
                lid = self._id()
                self.cells.append(f'<mxCell id="{lid}" value="{esc(lbl)}" style="edgeLabel;html=1;fontSize=11;fontStyle=1;" vertex="1" connectable="0" parent="{cid}">'
                                  f'<mxGeometry x="{(pos - 0.5) * 2}" relative="1" as="geometry"><mxPoint as="offset"/></mxGeometry></mxCell>')
        path = [(x1, y1), *pts, (x2, y2)]
        d = 'M' + ' L'.join(f'{px:.1f} {py:.1f}' for px, py in path)
        dash = ' stroke-dasharray="6 4"' if dashed else ''
        marker = {'open': ' marker-end="url(#open)"', 'triangle': ' marker-end="url(#tri)"', 'diamond': ' marker-end="url(#dia)"', None: ''}[arrow]
        mstart = ' marker-start="url(#dias)"' if head == 'diamond' else ''
        s = f'<path d="{d}" fill="none" stroke="{INK}" stroke-width="1.3"{dash}{marker}{mstart}/>'
        # libellés : milieu, et près de chaque extrémité
        if label:
            mx, my = self._along(path, 0.5)
            s += f'<rect x="{mx - len(label) * 3.3 - 4}" y="{my - 11}" width="{len(label) * 6.6 + 8}" height="16" fill="#fff" opacity="0.9"/>'
            s += f'<text x="{mx}" y="{my + 1}" text-anchor="middle" font-size="11" font-style="italic" fill="{INK}">{esc(label)}</text>'
        for lbl, t in ((a_label, 0.1), (b_label, 0.9)):
            if lbl:
                lx, ly = self._along(path, t)
                s += f'<rect x="{lx - len(lbl) * 3.4 - 3}" y="{ly - 10}" width="{len(lbl) * 6.8 + 6}" height="15" fill="#fff" opacity="0.85"/>'
                s += f'<text x="{lx}" y="{ly + 1}" text-anchor="middle" font-size="11" font-weight="700" fill="{PRIMARY}">{esc(lbl)}</text>'
        self.svg.append(s)

    @staticmethod
    def _along(path, t):
        lengths = [math.dist(path[i], path[i + 1]) for i in range(len(path) - 1)]
        target = sum(lengths) * t
        for i, seg in enumerate(lengths):
            if target <= seg or i == len(lengths) - 1:
                f = target / seg if seg else 0
                return path[i][0] + (path[i + 1][0] - path[i][0]) * f, path[i][1] + (path[i + 1][1] - path[i][1]) * f
            target -= seg
        return path[-1]

    def line(self, x1, y1, x2, y2, dashed=False, arrow=None, label='', color=INK, label_above=True, width=1.3):
        cid = self._id()
        end = {'open': 'endArrow=open;', 'block': 'endArrow=block;endFill=1;', None: 'endArrow=none;'}[arrow]
        style = f'html=1;{end}strokeColor={color};fontSize=11;fontFamily=Segoe UI;verticalAlign=bottom;' + ('dashed=1;' if dashed else '')
        self.cells.append(f'<mxCell id="{cid}" value="{esc(label)}" style="{style}" edge="1" parent="1">'
                          f'<mxGeometry relative="1" as="geometry"><mxPoint x="{x1}" y="{y1}" as="sourcePoint"/><mxPoint x="{x2}" y="{y2}" as="targetPoint"/></mxGeometry></mxCell>')
        dash = ' stroke-dasharray="6 4"' if dashed else ''
        marker = {'open': ' marker-end="url(#open)"', 'block': ' marker-end="url(#blk)"', None: ''}[arrow]
        s = f'<line x1="{x1}" y1="{y1}" x2="{x2}" y2="{y2}" stroke="{color}" stroke-width="{width}"{dash}{marker}/>'
        if label:
            tx = (x1 + x2) / 2
            s += f'<rect x="{tx - len(label) * 3.25 - 4}" y="{min(y1, y2) - 18}" width="{len(label) * 6.5 + 8}" height="15" fill="#fff" opacity="0.92"/>'
            s += f'<text x="{tx}" y="{min(y1, y2) - 6}" text-anchor="middle" font-size="11.5" fill="{INK}">{esc(label)}</text>'
        self.svg.append(s)

    # ---------- sortie ----------
    def save(self):
        drawio = ('<mxfile host="vite-gourmand-diagrams"><diagram name="' + esc(self.title) + '" id="d1">'
                  f'<mxGraphModel dx="{self.w}" dy="{self.h}" grid="1" gridSize="10" page="1" pageWidth="{self.w}" pageHeight="{self.h}">'
                  '<root><mxCell id="0"/><mxCell id="1" parent="0"/>' + ''.join(self.cells) + '</root></mxGraphModel></diagram></mxfile>')
        (OUT / f'{self.name}.drawio').write_text(drawio, encoding='utf-8')
        defs = (f'<defs><marker id="open" markerWidth="12" markerHeight="10" refX="11" refY="5" orient="auto"><path d="M0 0 L11 5 L0 10" fill="none" stroke="{INK}" stroke-width="1.4"/></marker>'
                f'<marker id="blk" markerWidth="12" markerHeight="10" refX="11" refY="5" orient="auto"><path d="M0 0 L11 5 L0 10 Z" fill="{INK}"/></marker>'
                f'<marker id="tri" markerWidth="14" markerHeight="12" refX="13" refY="6" orient="auto"><path d="M0 0 L13 6 L0 12 Z" fill="#fff" stroke="{INK}"/></marker>'
                f'<marker id="dia" markerWidth="16" markerHeight="10" refX="15" refY="5" orient="auto"><path d="M0 5 L7 0 L15 5 L7 10 Z" fill="{INK}"/></marker>'
                f'<marker id="dias" markerWidth="16" markerHeight="10" refX="1" refY="5" orient="auto"><path d="M0 5 L7 0 L15 5 L7 10 Z" fill="{INK}"/></marker></defs>')
        svg = (f'<svg xmlns="http://www.w3.org/2000/svg" width="{self.w}" height="{self.h}" viewBox="0 0 {self.w} {self.h}" font-family="{FONT}">'
               f'{defs}<rect width="100%" height="100%" fill="#fff"/>'
               f'<text x="24" y="34" font-size="20" font-weight="700" fill="{PRIMARY}">{esc(self.title)}</text>'
               + ''.join(self.svg) + '</svg>')
        (OUT / f'{self.name}.svg').write_text(svg, encoding='utf-8')
        print(f'{self.name} : {self.w}×{self.h}, {len(self.cells)} éléments')


# =====================================================================
# 1. Diagramme de classes UML (données + fonctions de l'application)
# =====================================================================
def classes():
    d = Diagram('diagramme-classes', 'Vite & Gourmand — Diagramme de classes UML (entités, méthodes, services)', 2280, 1640)
    d.rect(20, 50, 1500, 1570, 'MariaDB (relationnel)', stroke=PRIMARY, dashed=True)
    d.rect(1540, 50, 720, 640, 'MongoDB (documents)', fill='none', stroke='#198754', dashed=True)
    d.rect(1540, 710, 720, 910, 'Services applicatifs (règles métier)', fill='none', stroke='#2c5aa0', dashed=True)

    d.box('user', 40, 90, 330, 'User', [
        ['🔑 id : int', 'email : string (unique)', 'password : string (bcrypt)', 'role : user | employee | admin',
         'firstName, lastName : string', 'phone, address : string', 'isActive : bool', 'failedAttempts : int, lockedUntil : datetime',
         'resetTokenHash : string, resetTokenExpiresAt'],
        ['+ register(data) : int', '+ login(email, password)', '+ requestPasswordReset(email)', '+ resetPassword(token, password)',
         '+ updateProfile(data)', '+ exportData() : json', '+ deleteAccount(password)']])
    d.box('notif', 40, 520, 330, 'UserNotification', [
        ['🔑 id : int', 'type : order | quote', 'title, message : string', 'isRead : bool', 'createdAt : datetime'],
        ['+ notify(user, title, message)', '+ feed(afterId) : unread, items', '+ markRead() / markAllRead()']])
    d.box('quote', 40, 790, 330, 'QuoteRequest', [
        ['🔑 id : int', 'email, firstName, lastName, phone', 'eventDate : date, numberOfPeople : int', 'serviceType, eventLocation, postalCode',
         'requestDetails : text', 'status : new … closed', 'employeeReply : text'],
        ['+ submit(data)', '+ answer(status, reply)']])
    d.box('hours', 40, 1110, 330, 'OpeningHours', [
        ['🔑 dayOfWeek : 1..7', 'isOpen : bool', 'morningStart/End, eveningStart/End'],
        ['+ slots(date) : list (pas de 15 min)', '+ update(week)']])

    d.box('order', 460, 90, 400, 'Order', [
        ['🔑 id : int, orderNumber : string', 'numberOfPeople : int', 'deliveryDate : date, deliveryTime : time',
         'serviceType : delivery | pickup | on_site', 'deliveryAddress, postalCode, city', 'addressStatus : verified | manual',
         'deliveryZone : bordeaux | zone | outside', 'deliveryDistanceKm, deliveryCost : decimal', 'menuPrice, discountRate, totalPrice',
         'customization : text (allergies)', 'contactPhone : string', 'status : pending … completed | cancelled',
         'equipmentLoaned : bool, cancellationReason'],
        ['+ create(menu, people, items, address…)', '+ modify(changes)  [client : délai]', '+ cancel(reason)',
         '+ changeStatus(status, contactMode)', '+ modificationDeadline() : datetime', '+ isDeliveryCostPending() : bool']])
    d.box('history', 460, 640, 400, 'OrderStatusHistory', [
        ['🔑 id : int', 'status : enum', 'changedAt : datetime', 'notes : text (mode de contact, motif)']])
    d.box('item', 460, 850, 400, 'OrderItem  (choix du client)', [
        ['🔑 id : int', 'category : apéritif … digestif', 'quantity : int (convives)', 'withoutAllergens : list<int>'],
        ['+ normalize(menu, people, raw) : list', '+ rescale(old, new) : list', '+ describe() : texte']])

    d.box('menu', 960, 90, 400, 'Menu', [
        ['🔑 id : int', 'title, description : string', 'theme : Classique | Noël | Pâques | Évènement', 'dietaryRegime : classic | vegetarian | vegan',
         'minPeople : int', 'basePrice : decimal (pour minPeople)', 'conditions : text', 'availableStock : int', 'isActive : bool'],
        ['+ pricePerPerson() : decimal', '+ quote(people) : total, remise', '+ composition() : par catégorie', '+ filter(prix, thème, régime, personnes)']])
    d.box('dish', 960, 610, 400, 'Dish', [
        ['🔑 id : int', 'name, description : string', 'category : aperitif | starter | main | side | dessert | digestif',
         'dietaryRegime : enum', 'containsAlcohol : bool', 'isActive : bool'],
        ['+ allergens() : list', '+ canReplaceIn(menu) : bool']])
    d.box('allergen', 1040, 1000, 240, 'Allergen', [['🔑 id : int', 'name : string', 'code : string']])

    d.edge('user', 'order', 'passe', '1', '0..*')
    d.edge('user', 'notif', 'reçoit', '1', '0..*')
    d.edge('user', 'quote', '', '0..1', '0..*', via=[(30, 300), (30, 880)])
    d.edge('order', 'notif', 'concerne', '0..1', '0..*', via=[(420, 470), (420, 600)])
    d.edge('order', 'history', 'suivi', '1', '1..*', head='diamond')
    d.edge('order', 'item', 'composition', '1', '1..*', head='diamond', via=[(440, 560), (440, 900)])
    d.edge('menu', 'order', '', '1', '0..*')
    d.edge('menu', 'dish', 'compose (position)', '0..*', '1..*')
    d.edge('item', 'dish', 'plat servi', '0..*', '1')
    d.edge('item', 'dish', 'emplacement d’origine', '0..*', '1', dashed=True, via=[(910, 780)])
    d.edge('dish', 'allergen', 'contient', '0..*', '0..*')

    d.box('img', 1560, 90, 320, 'menu_images', [['menuId, position', 'url, altText, source'], ['+ add() / move() / delete()']], fill=MONGO, title_fill='#198754', stereotype='document')
    d.box('comment', 1920, 90, 320, 'comments', [['userId, menuId, orderId', 'rating : 1..5, comment', 'isValidated : bool'], ['+ create() / validate() / reject()']], fill=MONGO, title_fill='#198754', stereotype='document')
    d.box('stats', 1700, 390, 400, 'menu_statistics', [['menuId, menuTitle', 'periodIdentifier, periodStart, periodEnd', 'orderCount : int, revenue : decimal'], ['+ aggregateAndStore(période)', '+ byPeriod() : graphique admin']], fill=MONGO, title_fill='#198754', stereotype='document')
    d.edge('menu', 'img', 'galerie', '1', '0..*', dashed=True, via=[(1450, 150)])
    d.edge('order', 'comment', 'avis', '1', '0..1', dashed=True, via=[(870, 570), (1500, 570), (1500, 250), (2080, 250)])
    d.edge('menu', 'stats', 'statistiques', '1', '0..*', dashed=True, via=[(1400, 470)])

    svc = [
        ('pricing', 1560, 750, 'MenuPricing', ['+ perPerson(base, min)', '+ quote(base, min, people)', '+ discountFrom(min) : min + 5']),
        ('compo', 1910, 750, 'OrderCompositionService', ['+ options(menu)', '+ normalize(menu, people, raw)', '+ rescale(rows, old, new)']),
        ('delivery', 1560, 920, 'DeliveryDistanceService', ['+ check(adresse) : zone, frais', '+ quote(adresse, manuel, horsZone)', 'Bordeaux 0 € · 25 km · hors zone']),
        ('geo', 1910, 920, 'GeoService (OpenStreetMap)', ['+ suggest(texte)  → Photon', '+ geocode(rue, cp, ville)', '+ drivingKm()  → OSRM']),
        ('policy', 1560, 1110, 'OrderModificationPolicy', ['+ deadline(order)', '15 min / 24 h / 48 h', '+ customerCanChange(order)']),
        ('notifsvc', 1910, 1110, 'NotificationService', ['+ notify(user, …)', '+ notifyStaff(…)', '+ feed(user, afterId)']),
        ('mail', 1560, 1300, 'MailService (PHPMailer)', ['+ sendWelcomeEmail()', '+ sendOrderConfirmationEmail()', '+ sendOrderStatusEmail()']),
        ('privacy', 1910, 1300, 'PrivacyService (RGPD)', ['+ export(user) : json', '+ deleteAccount(user, pw)', 'anonymise, garde la compta']),
        ('security', 1560, 1470, 'Security / Auth / Staff / Admin', ['CSRF, limite de débit, en-têtes', 'rôle relu en base à chaque requête']),
        ('policypw', 1910, 1470, 'PasswordPolicy', ['+ assertStrong(pw) : 10 car., 4 types', '+ hash() : bcrypt']),
    ]
    for key, x, y, title, ops in svc:
        d.box(key, x, y, 330, title, [ops], fill=SERVICE, title_fill='#2c5aa0', stereotype='service')
    d.edge('delivery', 'geo', 'utilise', arrow='open', dashed=True)
    d.edge('compo', 'pricing', '', arrow='open', dashed=True)
    d.text(40, 1600, 'Légende : trait plein = association (multiplicités en rouge) ; losange plein = composition ; pointillés = lien entre MariaDB et MongoDB (par identifiant).', 12, INK, italic=True)
    d.save()


# =====================================================================
# 2. MCD (Merise)
# =====================================================================
def mcd():
    d = Diagram('mcd', 'Vite & Gourmand — Modèle conceptuel de données (Merise)', 1900, 1180)
    ent = lambda key, x, y, title, attrs: d.box(key, x, y, 250, title, [attrs])
    ent('USER', 60, 90, 'UTILISATEUR', ['🔑 id', 'email', 'mot_de_passe (haché)', 'rôle', 'prénom, nom', 'téléphone, adresse', 'actif', 'échecs, verrouillé_jusqu’à', 'jeton_réinitialisation'])
    ent('ORDER', 640, 90, 'COMMANDE', ['🔑 id', 'numéro', 'nb_personnes', 'date, heure prestation', 'mode (livraison, retrait, sur place)', 'adresse, code postal, ville', 'statut_adresse, zone', 'distance_km, frais_livraison', 'prix_menu, remise, total', 'précisions_cuisine', 'téléphone_contact', 'statut', 'matériel_prêté, motif_annulation'])
    ent('MENU', 1260, 90, 'MENU', ['🔑 id', 'titre, description', 'thème, régime', 'nb_personnes_min', 'prix_base (pour le minimum)', 'conditions', 'stock_disponible', 'actif'])
    ent('DISH', 1260, 620, 'PLAT', ['🔑 id', 'nom, description', 'catégorie (apéritif … digestif)', 'régime', 'contient_alcool', 'actif'])
    ent('ALLERGEN', 1620, 900, 'ALLERGÈNE', ['🔑 id', 'nom', 'code'])
    ent('HIST', 640, 620, 'HISTORIQUE_STATUT', ['🔑 id', 'statut', 'date_changement', 'notes (contact, motif)'])
    ent('NOTIF', 60, 620, 'NOTIFICATION', ['🔑 id', 'type', 'titre, message', 'lue', 'date'])
    ent('QUOTE', 60, 860, 'DEMANDE_DEVIS', ['🔑 id', 'e-mail, identité, téléphone', 'date, nb_personnes', 'mode, lieu, code postal', 'détails, statut, réponse'])
    ent('HOURS', 640, 930, 'HORAIRE', ['🔑 jour (1 à 7)', 'ouvert', 'midi : début, fin', 'soir : début, fin'])

    assoc = [('PASSER', 420, 230, 'USER', 'ORDER', '0,n', '1,1'), ('CONCERNER', 1040, 230, 'ORDER', 'MENU', '1,1', '0,n'),
             ('COMPOSER\n(position)', 1385, 470, 'MENU', 'DISH', '1,n', '0,n'), ('CONTENIR', 1660, 780, 'DISH', 'ALLERGEN', '0,n', '0,n'),
             ('CHOISIR\n(quantité, sans_allergènes,\nemplacement)', 1030, 560, 'ORDER', 'DISH', '1,n', '0,n'),
             ('SUIVRE', 765, 545, 'ORDER', 'HIST', '1,n', '1,1'), ('RECEVOIR', 185, 545, 'USER', 'NOTIF', '0,n', '1,1'),
             ('PORTER_SUR', 470, 700, 'ORDER', 'NOTIF', '0,n', '0,1'), ('DEMANDER', 420, 900, 'USER', 'QUOTE', '0,n', '0,1')]
    for name, cx, cy, a, b, ca, cb in assoc:
        key = 'A_' + name.split('\n')[0]
        d.ellipse(key, cx, cy, 190 if '\n' in name else 130, 64 if name.count('\n') > 1 else 46, name)
        d.edge(a, key, '', ca, '', via=[(340, 560), (340, 900)] if key == 'A_DEMANDER' else None)
        d.edge(key, b, '', '', cb)
    d.rect(1260, 1010, 330, 140, 'MongoDB (hors MCD relationnel)\nmenu_images · comments · menu_statistics\nreliés par menuId, orderId, userId', fill=MONGO, stroke='#198754', dashed=True, label_pos='middle', font=12)
    d.text(60, 1160, 'Cardinalités min,max lues depuis l’entité vers l’association. Les associations CHOISIR et COMPOSER portent des attributs (tables order_items, menu_dishes).', 12, INK, italic=True)
    d.save()


# =====================================================================
# 3. Cas d'utilisation
# =====================================================================
def usecases():
    d = Diagram('cas-utilisation', 'Vite & Gourmand — Diagramme de cas d’utilisation', 1800, 1240)
    d.rect(300, 70, 1160, 1150, 'Site Vite & Gourmand', stroke=PRIMARY)
    d.actor('visitor', 140, 170, 'Visiteur')
    d.actor('client', 140, 560, 'Client')
    d.actor('employee', 1640, 400, 'Employé')
    d.actor('admin', 1640, 860, 'Administrateur')
    d.actor('osm', 140, 1000, 'Cartographie OSM')
    d.actor('smtp', 1640, 1110, 'Serveur e-mail')
    d.edge('client', 'visitor', '', arrow='triangle')
    d.edge('admin', 'employee', '', arrow='triangle')

    left = [('uc_menus', 160, 'Consulter les menus\n(prix par personne, inclus)'), ('uc_filter', 230, 'Filtrer : personnes, thème,\nrégime, budget'),
            ('uc_detail', 300, 'Voir le détail d’un menu\n(plats, allergènes, conditions)'), ('uc_contact', 370, 'Contacter l’entreprise'),
            ('uc_quote', 430, 'Demander un devis'), ('uc_register', 490, 'Créer un compte\n(mail de bienvenue)'), ('uc_login', 560, 'Se connecter /\nmot de passe oublié')]
    for key, y, label in left:
        d.ellipse(key, 520, y, 300, 52, label)
        d.edge('visitor', key)
    client = [('uc_order', 650, 'Commander un menu'), ('uc_compose', 730, 'Composer son menu\n(choix, remplacement, sans allergène)'),
              ('uc_address', 820, 'Vérifier l’adresse\n(Bordeaux + 25 km)'), ('uc_modify', 900, 'Modifier / annuler\n(avant acceptation, dans le délai)'),
              ('uc_track', 980, 'Suivre sa commande\n(notifications, e-mails)'), ('uc_review', 1050, 'Donner un avis'),
              ('uc_rgpd', 1130, 'Gérer son profil, exporter\nou supprimer ses données')]
    for key, y, label in client:
        x = 640 if key in ('uc_compose', 'uc_address') else 520
        d.ellipse(key, x, y, 310, 54, label)
        if key not in ('uc_compose', 'uc_address'):
            d.edge('client', key)
    d.edge('uc_order', 'uc_compose', '«include»', dashed=True, arrow='open')
    d.edge('uc_order', 'uc_address', '«include»', dashed=True, arrow='open', via=[(860, 650), (860, 820)])
    d.edge('osm', 'uc_address', via=[(330, 860)])
    staff = [('uc_orders', 200, 'Traiter les commandes\n(statuts, après contact client)'), ('uc_catalog', 290, 'Gérer menus, plats,\ngaleries et horaires'),
             ('uc_moderate', 380, 'Modérer les avis'), ('uc_customers', 460, 'Gérer clients et devis'),
             ('uc_staffnotif', 540, 'Recevoir les nouvelles\ncommandes (cloche)')]
    for key, y, label in staff:
        d.ellipse(key, 1180, y, 320, 54, label)
        d.edge('employee', key)
    admin = [('uc_employees', 790, 'Créer / désactiver\nles comptes employés'), ('uc_stats', 880, 'Chiffre d’affaires et\nstatistiques (MongoDB)')]
    for key, y, label in admin:
        d.ellipse(key, 1180, y, 320, 54, label)
        d.edge('admin', key)
    d.edge('smtp', 'uc_track', '', dashed=True, via=[(1300, 1110), (900, 1010)])
    d.text(320, 1210, 'Flèche à triangle : héritage (le client fait tout ce que fait le visiteur ; l’administrateur tout ce que fait l’employé).', 12, INK, italic=True)
    d.save()


# =====================================================================
# 4. Séquences
# =====================================================================
def sequence(name, title, participants, messages, width):
    gap = (width - 240) / (len(participants) - 1)
    xs = {key: 120 + i * gap for i, (key, _) in enumerate(participants)}
    y0, step = 80, 46
    height = y0 + 90 + len(messages) * step + 60
    d = Diagram(name, title, int(width), int(height))
    for key, label in participants:
        lines = label.split('\n')
        d.rect(xs[key] - 85, y0, 170, 26 + 16 * len(lines), label, fill=SOFT, stroke=PRIMARY, label_pos='middle', font=12, rounded=True)
        d.line(xs[key], y0 + 26 + 16 * len(lines), xs[key], height - 30, dashed=True, color='#9aa5b1', width=1)
    y = y0 + 110
    for m in messages:
        kind = m[0]
        if kind == 'note':
            _, a, b, text = m
            x1, x2 = min(xs[a], xs[b]) - 60, max(xs[a], xs[b]) + 60
            need = len(text) * 6.6 + 30
            if x2 - x1 < need:
                mid = (x1 + x2) / 2
                x1, x2 = max(10, mid - need / 2), min(width - 10, mid + need / 2)
            d.rect(x1, y - 22, x2 - x1, 32, text, fill='#fff8e1', stroke='#b58105', label_pos='middle', font=11)
        elif kind == 'self':
            _, a, text = m
            x = xs[a]
            d.line(x, y - 14, x + 40, y - 14)
            d.line(x + 40, y - 14, x + 40, y + 4)
            d.line(x + 40, y + 4, x + 4, y + 4, arrow='block')
            d.text(x + 48, y - 2, text, 11.5, INK)
        else:
            _, a, b, text = m
            d.line(xs[a], y, xs[b], y, dashed=(kind == 'ret'), arrow='open' if kind == 'ret' else 'block', label=text)
        y += step
    d.save()


def sequences():
    sequence('sequence-inscription', 'Séquence — Inscription, mail de bienvenue et connexion',
             [('v', 'Visiteur'), ('nav', 'Navigateur\n(password.js)'), ('sec', 'Middleware\nSecurity / Guest'), ('ctl', 'AuthController'),
              ('svc', 'AuthService\nPasswordPolicy'), ('db', 'MariaDB'), ('mail', 'MailService\n→ SMTP')],
             [('msg', 'v', 'nav', 'Remplit le formulaire, mot de passe choisi ou généré'),
              ('self', 'nav', 'Afficher / masquer, confirmation identique'),
              ('msg', 'nav', 'sec', 'POST /register + jeton CSRF'),
              ('self', 'sec', 'Jeton CSRF valide, limite 3 essais / 5 min'),
              ('msg', 'sec', 'ctl', 'register()'),
              ('msg', 'ctl', 'svc', 'Champs, téléphone, consentement RGPD, politique mdp'),
              ('msg', 'svc', 'db', 'INSERT users (bcrypt, rôle « user », actif)'),
              ('ret', 'db', 'svc', 'id du compte'),
              ('msg', 'ctl', 'mail', 'sendWelcomeEmail() — sans mot de passe'),
              ('note', 'ctl', 'mail', 'Envoi non bloquant : le compte est gardé si le mail échoue'),
              ('ret', 'ctl', 'nav', '302 /login + message « compte créé »'),
              ('msg', 'nav', 'ctl', 'POST /login (e-mail, mot de passe)'),
              ('msg', 'ctl', 'db', 'Lecture du compte, password_verify()'),
              ('self', 'ctl', 'session_regenerate_id(), 5 échecs → blocage'),
              ('ret', 'ctl', 'nav', 'Connecté : accès à la commande')],
             1500)
    sequence('sequence-commande', 'Séquence — Commande d’un menu (personnes, composition, adresse, validation, suivi)',
             [('c', 'Client'), ('nav', 'Navigateur\n(order.js, composition.js)'), ('ctl', 'OrderController\nAddressController'),
              ('geo', 'DeliveryDistance\nGeoService'), ('osm', 'Photon / OSRM\n(OpenStreetMap)'), ('svc', 'OrderService\nComposition, Pricing'),
              ('db', 'MariaDB'), ('notif', 'Mail +\nNotifications')],
             [('msg', 'c', 'nav', 'Choisit le nombre de personnes (ex. 15)'),
              ('msg', 'nav', 'ctl', 'GET /orders/menu-options?number_of_people=15'),
              ('ret', 'ctl', 'nav', 'Menus possibles + total (−10 % dès min + 5)'),
              ('msg', 'nav', 'ctl', 'GET /orders/menu-composition?menu_id'),
              ('ret', 'ctl', 'nav', 'Catégories, options, remplacements, allergènes'),
              ('self', 'nav', 'Répartit les convives, variantes « sans gluten »'),
              ('msg', 'nav', 'ctl', 'GET /api/address/autocomplete puis /check'),
              ('msg', 'ctl', 'geo', 'check(rue, code postal, ville)'),
              ('msg', 'geo', 'osm', 'Recherche (Photon), itinéraire (OSRM)'),
              ('ret', 'geo', 'ctl', 'Vérifiée ? zone : Bordeaux / 25 km / hors zone, frais'),
              ('note', 'c', 'nav', 'Hors zone : retrait gratuit ou livraison acceptée ; introuvable : saisie manuelle'),
              ('msg', 'nav', 'ctl', 'POST /orders (CSRF, items, consentement allergies)'),
              ('msg', 'ctl', 'svc', 'createOrder()'),
              ('self', 'svc', 'normalize(items), créneau, MenuPricing.quote()'),
              ('msg', 'svc', 'geo', 'quote(adresse) — revérifiée côté serveur'),
              ('msg', 'svc', 'db', 'Transaction : orders, order_items, stock − 1, historique'),
              ('msg', 'svc', 'notif', 'Mail de confirmation + notification client et équipe'),
              ('ret', 'ctl', 'nav', '302 → « Commande envoyée ! » + suivi en 5 étapes'),
              ('note', 'nav', 'svc', 'Modification / annulation : en attente ET délai 15 min / 24 h / 48 h'),
              ('msg', 'notif', 'nav', 'Statut changé par l’équipe → cloche, bulle, e-mail')],
             1800)


if __name__ == '__main__':
    OUT.mkdir(parents=True, exist_ok=True)
    classes()
    mcd()
    usecases()
    sequences()
