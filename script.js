// 獲取DOM元素
const modal = document.getElementById('editModal');
const closeBtn = document.querySelector('.close');

// 關閉彈窗
closeBtn.onclick = function() {
    modal.style.display = "none";
}

// 點擊彈窗外部關閉彈窗
window.onclick = function(event) {
    if (event.target == modal) {
        modal.style.display = "none";
    }
}

// 顯示編輯表單
function showEditForm(id, group, name, title, url) {
    // 填充表單數據
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_group').value = group;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_title').value = title;
    document.getElementById('edit_url').value = url;
    
    // 顯示彈窗
    modal.style.display = "block";
}

// 按ESC鍵關閉彈窗
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        modal.style.display = "none";
    }
});

// 表單驗證
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.style.borderColor = '#dc3545';
                } else {
                    field.style.borderColor = '#e1e5e9';
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('請填寫所有必填欄位');
            }
        });
    });
    
    // URL格式驗證
    const urlInputs = document.querySelectorAll('input[type="url"]');
    urlInputs.forEach(input => {
        input.addEventListener('blur', function() {
            const url = this.value.trim();
            if (url && !isValidUrl(url)) {
                this.style.borderColor = '#dc3545';
                this.setCustomValidity('請輸入有效的網址格式');
            } else {
                this.style.borderColor = '#e1e5e9';
                this.setCustomValidity('');
            }
        });
    });
});

// URL驗證函數
function isValidUrl(string) {
    try {
        new URL(string);
        return true;
    } catch (_) {
        return false;
    }
}

// 平滑滾動到表單
function scrollToForm() {
    const form = document.querySelector('.upload-form');
    form.scrollIntoView({ behavior: 'smooth' });
}

// 添加一些互動效果
document.addEventListener('DOMContentLoaded', function() {
    // 為作業卡片添加點擊效果
    const cards = document.querySelectorAll('.assignment-card');
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
    
    // 為按鈕添加點擊效果
    const buttons = document.querySelectorAll('button');
    buttons.forEach(button => {
        button.addEventListener('click', function() {
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = 'scale(1)';
            }, 100);
        });
    });
});
