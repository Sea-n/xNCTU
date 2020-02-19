function init() {
	document.getElementById('body-area').oninput = checkForm;
	document.getElementById('captcha-input').oninput = checkForm;
	checkForm();
}

function checkForm() {
	var bodyArea = document.getElementById('body-area');
	var bodyField = document.getElementById('body-field');
	var bodyWc = document.getElementById('body-wc');
	var captchaInput = document.getElementById('captcha-input');
	var captchaField = document.getElementById('captcha-field');
	var submit = document.getElementById('submit');

	bodyField.classList.remove('error', 'warning');
	captchaField.classList.remove('error');
	submit.classList.remove('disabled');

	var len = bodyArea.value.length;
	bodyWc.innerText = len;

	if (len > 870) {
		bodyField.classList.add('error');
		submit.classList.add('disabled');
	} else if (len > 690)
		bodyField.classList.add('warning');
	else if (len < 10)
		submit.classList.add('disabled');

	if (captchaInput.value.length == 0)
		submit.classList.add('disabled');
	else if (captchaInput.value.length != captchaInput.dataset.len)
		captchaField.classList.add('error');
}

window.onload = init;
