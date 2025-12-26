#!/bin/bash
# SICOKRO Setup Script
# Jalankan script ini untuk setup awal project

echo "=========================================="
echo "SICOKRO - Setup Script"
echo "=========================================="
echo ""

# Warna untuk output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Cek apakah folder sicokro ada
if [ ! -d "app" ] || [ ! -d "public" ]; then
    echo -e "${RED}Error: Script harus dijalankan di root folder sicokro${NC}"
    exit 1
fi

echo -e "${YELLOW}1. Membuat struktur folder...${NC}"

# Buat folder-folder yang dibutuhkan
mkdir -p public/uploads/bukti_pembayaran
mkdir -p public/uploads/dokumen_bantuan
mkdir -p public/uploads/bukti_pengeluaran
mkdir -p logs
mkdir -p sql/backups
mkdir -p public/assets/css
mkdir -p public/assets/js
mkdir -p public/assets/images

echo -e "${GREEN}✓ Struktur folder berhasil dibuat${NC}"

echo -e "${YELLOW}2. Membuat file .gitkeep...${NC}"

# Buat .gitkeep untuk folder yang perlu di-track Git tapi kosong
touch public/uploads/.gitkeep
touch public/uploads/bukti_pembayaran/.gitkeep
touch public/uploads/dokumen_bantuan/.gitkeep
touch public/uploads/bukti_pengeluaran/.gitkeep
touch logs/.gitkeep
touch sql/backups/.gitkeep

echo -e "${GREEN}✓ File .gitkeep berhasil dibuat${NC}"

echo -e "${YELLOW}3. Set permission folder...${NC}"

# Set permission untuk folder uploads dan logs
chmod -R 755 public/uploads
chmod -R 755 logs

echo -e "${GREEN}✓ Permission berhasil diset${NC}"

echo -e "${YELLOW}4. Membuat file log...${NC}"

# Buat file log kosong
touch logs/error.log
touch logs/access.log
chmod 644 logs/*.log

echo -e "${GREEN}✓ File log berhasil dibuat${NC}"

echo -e "${YELLOW}5. Cek konfigurasi...${NC}"

# Cek apakah config.php ada
if [ -f "app/config.php" ]; then
    echo -e "${GREEN}✓ File config.php ditemukan${NC}"
else
    echo -e "${RED}✗ File config.php tidak ditemukan!${NC}"
    echo -e "${YELLOW}  Silakan buat file config.php di folder app/${NC}"
fi

# Cek apakah database sudah di-import
echo ""
echo -e "${YELLOW}6. Setup Database${NC}"
echo -e "${YELLOW}   Apakah Anda sudah import sql/sicokro.sql ke database? (y/n)${NC}"
read -r response

if [[ "$response" =~ ^([yY][eE][sS]|[yY])$ ]]; then
    echo -e "${GREEN}✓ Database sudah ready${NC}"
else
    echo -e "${YELLOW}   Jalankan command berikut untuk import database:${NC}"
    echo -e "${NC}   mysql -u root -p sicokro < sql/sicokro.sql${NC}"
fi

echo ""
echo -e "${GREEN}=========================================="
echo "Setup selesai!"
echo -e "==========================================${NC}"
echo ""
echo -e "${YELLOW}Langkah selanjutnya:${NC}"
echo "1. Edit app/config.php sesuai environment Anda"
echo "2. Import sql/sicokro.sql ke database"
echo "3. Akses aplikasi di: http://localhost/sicokro/public"
echo ""
echo -e "${YELLOW}Default login:${NC}"
echo "Email: admin@cokroaminoto.sch.id"
echo "Password: admin123"
echo ""
echo -e "${RED}PENTING: Ganti password default setelah login pertama!${NC}"
echo ""