# Wahlomat.local

**Wahlomat.local** ist ein lokal installiertes Webprojekt, das als interaktives Tool zur politischen Bildung dient.  
Es ermöglicht die Simulation von Wahl‑O‑Mat‑ähnlichen Entscheidungsfragen, um politische Positionen zu erkunden — komplett offline über XAMPP.

***

## 🚀 Features
- Lokale Entwicklungsumgebung mit **XAMPP / Apache / PHP**
- Dynamische Fragen- und Antwortlogik
- Übersichtliche Auswertung der Antworten
- Erweiterbar um eigene Themen, Parteien oder Fragen

***

## 💻 Installation

1. Repository klonen oder herunterladen:  
   `git clone https://github.com/<dein-user>/wahlomat.local.git`
2. Projekt in das XAMPP-Webverzeichnis legen:  
   `C:\xampp\htdocs\wahlomat`
3. In der `hosts`-Datei (Windows: `C:\Windows\System32\drivers\etc\hosts`) hinzufügen:  
   `127.0.0.1   wahlomat.local`
4. In Apache die virtuelle Domain konfigurieren (z. B. in `httpd-vhosts.conf`):

   ```
   <VirtualHost *:80>
       DocumentRoot "C:/xampp/htdocs/wahlomat"
       ServerName wahlomat.local
   </VirtualHost>
   ```

5. Starte Apache über das XAMPP Control Panel und öffne:  
   👉 [http://wahlomat.local](http://wahlomat.local)

***

## ⚙️ Technologien
- PHP 8.x  
- HTML / CSS / JavaScript  
- XAMPP / Apache  

***

## 🛠️ Entwicklung
- Fragen und Konfiguration: `/data/fragen.json`
- Anpassbare Templates: `/templates/`
- Styles: `/assets/css/`

***

## 🤝 Mitmachen
Pull Requests und Issues sind willkommen!  
Bitte formuliere Änderungen möglichst klar und nachvollziehbar.

***

## 📄 Lizenz
Dieses Projekt steht unter der **MIT License** – frei zur Nutzung, Veränderung und Weitergabe, auf eigenes Risiko.

***

## 📬 Kontakt
Entwickelt von **Thomas Kujawa**  
📧 thomas.kujawa+passauwahlomat@gmail.com

***

*Dieses Projekt dient rein zu Lern- und Demonstrationszwecken und steht in keiner Verbindung zur Bundeszentrale für politische Bildung oder dem offiziellen Wahl-O-Mat.*