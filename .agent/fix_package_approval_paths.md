# Complete Path Reference for package_approval.php

## File Location
```
RL/admin/bought_ads_package/approve/package_approval.php
```

## All Required Files and Their Correct Paths

### 1. Database Configuration
**File:** `config/db.php`  
**Location:** `RL/config/db.php`  
**Path from approve folder:** `../../../config/db.php`
```
approve/ → bought_ads_package/ → admin/ → RL/ → config/db.php
  (../)        (../../)          (../../../)
```

### 2. Email Service
**File:** `services/email.php`  
**Location:** `RL/services/email.php`  
**Path from approve folder:** `../../../services/email.php`
```
approve/ → bought_ads_package/ → admin/ → RL/ → services/email.php
  (../)        (../../)          (../../../)
```

### 3. Notification Function
**File:** `package_approval_notification_auto.php`  
**Location:** `RL/admin/notification/owner/ads_package_approval_notification/`  
**Path from approve folder:** `../../notification/owner/ads_package_approval_notification/package_approval_notification_auto.php`
```
approve/ → bought_ads_package/ → admin/ → notification/owner/.../
  (../)        (../../)
```

### 4. Invoice Generator
**File:** `invoice.php`  
**Location:** `RL/admin/bought_ads_package/approve/invoice/`  
**Path from approve folder:** `./invoice/invoice.php`
```
approve/ → invoice/invoice.php
  (same level, just subfolder)
```

### 5. Navbar
**File:** `navbar.php`  
**Location:** `RL/public/navbar/`  
**Path from approve folder:** `../../../public/navbar/navbar.php`
```
approve/ → bought_ads_package/ → admin/ → RL/ → public/navbar/navbar.php
  (../)        (../../)          (../../../)
```

## Final Code (Lines 1-5)
```php
<?php
require __DIR__ . '/../../../config/db.php';
require __DIR__ . '/../../../services/email.php';
require __DIR__ . '/../../notification/owner/ads_package_approval_notification/package_approval_notification_auto.php';
require __DIR__ . '/invoice/invoice.php';
```

## Directory Tree Visual
```
RL/
├── config/
│   └── db.php                           (../../../config/db.php)
├── services/
│   └── email.php                        (../../../services/email.php)
├── public/
│   └── navbar/
│       └── navbar.php                   (../../../public/navbar/navbar.php)
└── admin/
    ├── notification/
    │   └── owner/
    │       └── ads_package_approval_notification/
    │           └── package_approval_notification_auto.php  (../../notification/...)
    └── bought_ads_package/
        └── approve/
            ├── package_approval.php     ← WE ARE HERE
            └── invoice/
                └── invoice.php          (./invoice/invoice.php)
```

## Path Resolution Logic

**To go from `approve/` to root (`RL/`):**
- Need to go up 3 levels: `../../../`

**To go from `approve/` to `admin/`:**
- Need to go up 2 levels: `../../`

**To access files in same directory:**
- Just use `./filename` or relative path

## All Fixes Applied ✅
1. ✅ `config/db.php` - Changed from `../../` to `../../../`
2. ✅ `services/email.php` - Changed from `../../` to `../../../`
3. ✅ `notification/...` - Changed from `../` to `../../`
4. ✅ `navbar.php` - Changed from `../../` to `../../../`
5. ✅ `invoice/invoice.php` - Already correct (`./`)
