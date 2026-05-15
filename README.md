# 🥗 Taller Nutricional · Centro Metabólico

Landing page con integración de pagos vía **Transbank Webpay Plus** para el taller de baja de peso, reducción de grasa corporal y alimentación saludable.

---

## 📂 Estructura del proyecto

```
taller-landing/
├── index.html              ← Landing page principal
├── webpay-init.php         ← Inicia transacción Webpay
├── webpay-confirm.php      ← Confirma pago y notifica al cliente
├── config.php              ← Carga variables de entorno
├── composer.json           ← Dependencias (SDK Transbank)
├── .env.example            ← Plantilla de configuración
├── .env                    ← Credenciales reales (NO se sube a Git)
├── .gitignore              ← Archivos excluidos del repo
└── README.md               ← Este archivo
```

---

## 🚀 Despliegue local (para probar)

### 1. Clonar el repositorio

```bash
git clone https://github.com/TU-USUARIO/taller-landing.git
cd taller-landing
```

### 2. Instalar dependencias

```bash
composer install
```

### 3. Configurar variables de entorno

```bash
cp .env.example .env
```

Edita el archivo `.env` con tus datos. **Con las credenciales de prueba que vienen por defecto, el flujo de pago ya funciona** para que puedas probar todo.

### 4. Levantar servidor local

```bash
php -S localhost:8000
```

Abre `http://localhost:8000` en tu navegador.

---

## 💳 Tarjeta de prueba (Webpay Integración)

| Campo | Valor |
|---|---|
| Número | `4051 8856 0044 6623` |
| CVV | `123` |
| Fecha | cualquier fecha futura |
| RUT | `11.111.111-1` |
| Clave | `123` |

---

## 🌐 Despliegue en producción

### Paso 1: Sube los archivos a tu hosting

Cualquier hosting con PHP 7.4+ funciona. Recomendados en Chile:
- **HostingPlus** · **Hostinger** · **DonWeb** · **SiteGround**

### Paso 2: Instala dependencias en el servidor

Por SSH o terminal de cPanel:
```bash
composer install --no-dev
```

### Paso 3: Crea el archivo `.env` con tus credenciales reales

```bash
cp .env.example .env
nano .env
```

Modifica:
```env
APP_URL=https://tudominio.cl
TRANSBANK_ENV=production
TRANSBANK_COMMERCE_CODE=tu_commerce_code_real
TRANSBANK_API_KEY=tu_api_key_real
MAIL_FROM=contacto@centrometabolico.cl
```

### Paso 4: Contrata Webpay Plus

1. Regístrate en [transbankdevelopers.cl](https://www.transbankdevelopers.cl/)
2. Firma contrato con Transbank
3. Recibirás tu `commerce_code` y `api_key` de producción
4. Pégalos en el `.env`

### Paso 5: Permisos de carpetas

```bash
chmod 750 reservas/
chmod 600 .env
```

---

## 🔒 Seguridad

| ✅ | Las credenciales viven solo en `.env` (excluido de Git) |
| ✅ | Los datos de tarjeta NUNCA pasan por nuestro servidor (solo por Transbank) |
| ✅ | El SDK oficial maneja firma criptográfica de cada transacción |
| ✅ | Las reservas se guardan localmente en `/reservas/` (excluida de Git) |
| ⚠️ | Recomendado: migrar de archivos JSON a MySQL/PostgreSQL para escala |

---

## 🛠️ Personalización rápida

Variables que cambian con cada edición del taller (en `index.html`):

| Línea aprox. | Qué editar |
|---|---|
| Hero — `<span class="meta-value">` | Fecha y horario del taller |
| Urgency banner | Número de cupos disponibles |
| Price box | Precio del taller |
| Form hidden `value="50000"` | Precio en pesos sin puntos |
| Footer | WhatsApp y email de contacto |

---

## 📧 Notificaciones por email

Al confirmarse un pago, `webpay-confirm.php` envía automáticamente:
- Email al cliente con sus datos de inscripción
- Email al equipo de Centro Metabólico con el nuevo inscrito

Si tu hosting no envía con `mail()` nativo, considera SMTP vía PHPMailer + Brevo/SendGrid/Mailgun.

---

## 📞 Soporte

**Documentación oficial Transbank:**
[transbankdevelopers.cl/documentacion/webpay-plus](https://www.transbankdevelopers.cl/documentacion/webpay-plus)

**Centro Metabólico**
Ñuñoa, Santiago de Chile
contacto@centrometabolico.cl

---

© 2026 Centro Metabólico · Todos los derechos reservados
