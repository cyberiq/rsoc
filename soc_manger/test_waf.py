#!/usr/bin/env python
# -*- coding: utf-8 -*-
"""
اختبار جدار الحماية WAF
يمكن استخدام هذا الملف للتأكد من أن جميع القواعس تعمل بشكل صحيح
"""

import requests
import json
from time import sleep

BASE_URL = "http://localhost:5000"

def test_normal_request():
    """اختبار طلب عادي (يجب أن ينجح)"""
    print("✅ اختبار: طلب عادي")
    try:
        response = requests.get(f"{BASE_URL}/", timeout=5)
        if response.status_code == 200:
            print(f"   ✅ نجح - الحالة: {response.status_code}")
            return True
        else:
            print(f"   ❌ فشل - الحالة: {response.status_code}")
            return False
    except Exception as e:
        print(f"   ❌ خطأ: {e}")
        return False

def test_sqli():
    """اختبار SQL Injection"""
    print("✅ اختبار: SQL Injection")
    payload = "' OR '1'='1"
    try:
        response = requests.get(f"{BASE_URL}/?q={payload}", timeout=5)
        if response.status_code == 403:
            print(f"   ✅ تم حجب الهجوم - الحالة: {response.status_code}")
            return True
        else:
            print(f"   ❌ لم يتم حجب الهجوم - الحالة: {response.status_code}")
            return False
    except Exception as e:
        print(f"   ❌ خطأ: {e}")
        return False

def test_xss():
    """اختبار XSS"""
    print("✅ اختبار: XSS (Cross-Site Scripting)")
    payload = "<script>alert('XSS')</script>"
    try:
        response = requests.get(f"{BASE_URL}/?q={payload}", timeout=5)
        if response.status_code == 403:
            print(f"   ✅ تم حجب الهجوم - الحالة: {response.status_code}")
            return True
        else:
            print(f"   ❌ لم يتم حجب الهجوم - الحالة: {response.status_code}")
            return False
    except Exception as e:
        print(f"   ❌ خطأ: {e}")
        return False

def test_path_traversal():
    """اختبار Path Traversal"""
    print("✅ اختبار: Path Traversal")
    payload = "../../etc/passwd"
    try:
        response = requests.get(f"{BASE_URL}/?file={payload}", timeout=5)
        if response.status_code == 403:
            print(f"   ✅ تم حجب الهجوم - الحالة: {response.status_code}")
            return True
        else:
            print(f"   ❌ لم يتم حجب الهجوم - الحالة: {response.status_code}")
            return False
    except Exception as e:
        print(f"   ❌ خطأ: {e}")
        return False

def test_command_injection():
    """اختبار Command Injection"""
    print("✅ اختبار: Command Injection")
    payload = "; ls -la"
    try:
        response = requests.get(f"{BASE_URL}/?cmd={payload}", timeout=5)
        if response.status_code == 403:
            print(f"   ✅ تم حجب الهجوم - الحالة: {response.status_code}")
            return True
        else:
            print(f"   ❌ لم يتم حجب الهجوم - الحالة: {response.status_code}")
            return False
    except Exception as e:
        print(f"   ❌ خطأ: {e}")
        return False

def test_scanner_detection():
    """اختبار اكتشاف أدوات المسح"""
    print("✅ اختبار: اكتشاف SQLmap")
    headers = {"User-Agent": "sqlmap/1.3.0"}
    try:
        response = requests.get(f"{BASE_URL}/", headers=headers, timeout=5)
        if response.status_code == 403:
            print(f"   ✅ تم اكتشاف الأداة - الحالة: {response.status_code}")
            return True
        else:
            print(f"   ❌ لم يتم اكتشاف الأداة - الحالة: {response.status_code}")
            return False
    except Exception as e:
        print(f"   ❌ خطأ: {e}")
        return False

def test_json_payload():
    """اختبار JSON Payload"""
    print("✅ اختبار: JSON Payload (SQLi)")
    try:
        response = requests.post(
            f"{BASE_URL}/",
            json={"query": "' OR '1'='1"},
            timeout=5
        )
        if response.status_code == 403:
            print(f"   ✅ تم حجب الهجوم - الحالة: {response.status_code}")
            return True
        else:
            print(f"   ❌ لم يتم حجب الهجوم - الحالة: {response.status_code}")
            return False
    except Exception as e:
        print(f"   ❌ خطأ: {e}")
        return False

def test_stats():
    """اختبار الإحصائيات"""
    print("✅ اختبار: الحصول على الإحصائيات")
    try:
        response = requests.get(f"{BASE_URL}/api/stats", timeout=5)
        if response.status_code == 200:
            data = response.json()
            print(f"   ✅ نجح - عدد الـ IPs المحظورة: {data.get('total_banned_ips', 0)}")
            return True
        else:
            print(f"   ❌ فشل - الحالة: {response.status_code}")
            return False
    except Exception as e:
        print(f"   ❌ خطأ: {e}")
        return False

def test_health():
    """اختبار فحص الصحة"""
    print("✅ اختبار: فحص صحة النظام")
    try:
        response = requests.get(f"{BASE_URL}/api/health", timeout=5)
        if response.status_code == 200:
            print(f"   ✅ النظام صحي - الحالة: {response.status_code}")
            return True
        else:
            print(f"   ❌ خطأ في النظام - الحالة: {response.status_code}")
            return False
    except Exception as e:
        print(f"   ❌ خطأ: {e}")
        return False

def main():
    """تشغيل جميع الاختبارات"""
    print("=" * 60)
    print("🛡️  اختبار جدار الحماية WAF v3.0")
    print("=" * 60)
    print()
    
    # التحقق من أن الخادم يعمل
    print("🔍 التحقق من اتصال الخادم...")
    try:
        requests.get(f"{BASE_URL}/", timeout=5)
        print("✅ الخادم يعمل\n")
    except Exception as e:
        print(f"❌ الخادم غير متاح: {e}")
        print("تأكد من تشغيل: python mine.py")
        return
    
    # تشغيل الاختبارات
    tests = [
        ("الطلبات العادية", test_normal_request),
        ("SQL Injection", test_sqli),
        ("XSS", test_xss),
        ("Path Traversal", test_path_traversal),
        ("Command Injection", test_command_injection),
        ("اكتشاف أدوات المسح", test_scanner_detection),
        ("JSON Payloads", test_json_payload),
        ("الإحصائيات", test_stats),
        ("فحص الصحة", test_health),
    ]
    
    results = []
    for name, test_func in tests:
        results.append(test_func())
        print()
        sleep(0.5)  # انتظار صغير بين الاختبارات
    
    # النتائج النهائية
    print("=" * 60)
    print("📊 النتائج النهائية")
    print("=" * 60)
    passed = sum(results)
    total = len(results)
    percentage = (passed / total) * 100
    print(f"✅ نجح: {passed}/{total} ({percentage:.0f}%)")
    print()
    
    if percentage == 100:
        print("🎉 رائع! جميع الاختبارات نجحت!")
    elif percentage >= 80:
        print("👍 أداء جيد!")
    else:
        print("⚠️  هناك بعض المشاكل - يرجى التحقق من السجلات")

if __name__ == "__main__":
    main()
