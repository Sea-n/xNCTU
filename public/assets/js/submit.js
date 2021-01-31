let submitted = false;
let img_data = false;

function init() {
    if (document.getElementById('submit-post')) {
        const img = document.getElementById('img');
        document.getElementById('submit-post').onsubmit = submitForm;
        document.getElementById('body-area').oninput = formUpdate;
        img.oninput = formUpdate;
        img.onchange = updateImg;
        document.getElementById('captcha-input').oninput = formUpdate;

        window.addEventListener('beforeunload', function (e) {
            const bodyArea = document.getElementById('body-area');
            const len = bodyArea.value.length;
            if (len === 0 || submitted)
                return undefined;

            const confirmationMessage = '您確定要離開嗎？';
            (e || window.event).returnValue = confirmationMessage; //Gecko + IE
            return confirmationMessage; //Gecko + Webkit, Safari, Chrome etc.
        });


        window.addEventListener('paste', (e) => {
            const items = e.clipboardData.items;
            if(items === undefined)
                return;

            for (let i=0; i<items.length; i++) {
                if (items[i].type.indexOf('image') === -1)
                    continue;

                img_data = items[i].getAsFile();

                updateImgPreview();
            }
        });


        restoreForm();
    }
}

function updateImg() {
    const img = document.getElementById('img');
    const files = img.files;

    if (!files || !files[0])
        return;

    img_data = files[0];
    updateImgPreview();
}

function updateImgPreview() {
    const preview = document.getElementById('img-preview');

    if (!img_data) {
        preview.src = '';
        preview.parentElement.setAttribute('style', 'display: none !important;');
        return;
    }

    if (img_data.size > 50*1000*1000) {
        alert('圖片檔案過大！請壓縮至 50 MB 以下');
        img_data = false;
        updateImgPreview();
        return;
    }

    const reader = new FileReader();
    reader.onload = () => {
        preview.src = reader.result;
        preview.parentElement.style.display = '';
    };
    reader.readAsDataURL(img_data);
}

function restoreForm() {
    const bodyArea = document.getElementById('body-area');
    const captchaInput = document.getElementById('captcha-input');

    if (localStorage.getItem('draft'))
        bodyArea.value = localStorage.getItem('draft');

    if (localStorage.getItem('captcha'))
        captchaInput.value = localStorage.getItem('captcha');

    formUpdate();
}

function formUpdate() {
    const bodyArea = document.getElementById('body-area');
    const bodyField = document.getElementById('body-field');
    const bodyWc = document.getElementById('body-wc');
    const captchaInput = document.getElementById('captcha-input');
    const captchaField = document.getElementById('captcha-field');
    const submit = document.getElementById('submit');
    const preview = document.getElementById('warning-preview');

    bodyField.classList.remove('error', 'warning');
    captchaField.classList.remove('error');
    submit.classList.remove('disabled', 'negative', 'basic');

    const body = bodyArea.value;
    const len = body.length;
    bodyWc.innerText = len;

    if (img_data) {
        if (len > 870) {
            bodyField.classList.add('error');
        } else if (len > 690)
            bodyField.classList.add('warning');
    } else {
        if (len > 3600) {
            bodyField.classList.add('error');
        } else if (len > 3200)
            bodyField.classList.add('warning');
    }

    preview.style.display = 'none';
    if (body.includes('http')) {
        const lines = body.split('\n');

        const last = lines[lines.length - 1];
        if (!last.match(/^https?:\/\/[^\s]*$/)) {
            preview.style.display = '';
            submit.classList.add('negative');
        }

        /* Even if last line is URL, first line cannot be URL. */
        const first = lines[0];
        if (first.match(/https?:\/\//)) {
            preview.style.display = '';
            submit.classList.add('negative');
            submit.classList.add('disabled');
        }
    }

    const captcha = captchaInput.value;
    if (captcha.length > 0 &&
        captcha.length !== parseInt(captchaInput.dataset.len))
        captchaField.classList.add('error');

    localStorage.setItem('draft', body);
    localStorage.setItem('captcha', captcha);
}

function submitForm(e) {
    e.preventDefault();
    const body = document.getElementById('body-area');
    const captcha = document.getElementById('captcha-input');
    const anon = document.getElementById('anon');
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const submit = document.getElementById('submit');

    if (!checkForm()) {
        return;
    }

    submit.classList.add('disabled');

    const formData  = new FormData();
    formData.append('body', body.value);
    if (img_data)
        formData.append('img', img_data);
    formData.append('captcha', captcha.value);
    formData.append('csrf_token', csrf);
    if (anon.checked)
        formData.append('anon', '1');

    fetch('/api/posts', {
        method: 'POST',
        body: formData,
    }).then(resp => resp.json())
        .then((resp) => {
            if (!resp.ok) {
                alert(resp.msg);

                if (resp.uid)
                    location.href = '/review/' + resp.uid;
            }

            localStorage.setItem('draft', '');
            submitted = true;
            location.href = '/review/' + resp.uid;
        });
}

function checkForm() {
    const bodyArea = document.getElementById('body-area');
    const bodyField = document.getElementById('body-field');
    const img = document.getElementById('img');
    const captchaInput = document.getElementById('captcha-input');
    const captchaField = document.getElementById('captcha-field');
    let isValid = true;

    formUpdate();  // For clean & update warnings

    const len = bodyArea.value.length;
    if (len === 0) {
        bodyField.classList.add('error');
        isValid = false;
    }

    if ((img.files.length && len > 870) || len > 3600) {
        isValid = false;
    }

    if (captchaInput.value.length !== parseInt(captchaInput.dataset.len)) {
        captchaField.classList.add('error');
        isValid = false;
    }

    return isValid;
}

function changeAnon() {
    const anon = document.getElementById('anon');
    const wName = document.getElementById('warning-name');
    const wIp = document.getElementById('warning-ip');

    wName.style.display = 'none';
    wIp.style.display = 'none';
    if (anon.checked)
        wIp.style.display = '';
    else
        wName.style.display = '';
}

window.addEventListener('load', init);
