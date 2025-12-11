---
description: Panduan commit dan push git
---

Sebelum melakukan push ke git, cek dulu bagian stagging (abaikan file lain yang belum stagging), file apa saja yang berubah dan generate pesan commit ketentuan berikut: 

**Ketentuan:**

* Gunakan prefix: `feat`, `fix`, `refactor`, `docs`, `style`, `chore`.
* Maksimal **3 bullet point**.
* Format harus seperti contoh berikut:

```text
fix(ui): resolve issues

- Fix A...
- Fix B...
- Misc. improvement
```

lakukan commit pada file stagging kemudian push ke remote berikut :

- origin/main
- person/main