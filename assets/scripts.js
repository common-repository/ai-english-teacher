'use strict';
const aiet_openaiApiKey = aietScriptData.api;
const aiet_openaiEndpoint = "https://api.openai.com/v1/completions";

const aiet_grammarForm = document.getElementById("aiet_grammarForm");
const aiet_correctGrammar = document.getElementById("aiet_correct_grammar");
const aiet_rephraseSentences = document.getElementById("aiet_rephrase_sentences");

aiet_correctGrammar.addEventListener("click", function (event) {
	event.preventDefault();
	aiet_checkGrammar('correct')
	aiet_rephraseSentences.style.opacity = "1";
	aiet_correctGrammar.style.opacity = "0.7";
});

aiet_rephraseSentences.addEventListener("click", function (event) {
	event.preventDefault();
	aiet_checkGrammar('rephrase')
	aiet_rephraseSentences.style.opacity = "0.7";
	aiet_correctGrammar.style.opacity = "1";
});

function aiet_checkGrammar(prompt) {
	const inputText = document.getElementById("aiet_inputText").value;
	const outputDiv = document.getElementById("aiet_output");
	if (inputText == '') return aiet_outputDiv.innerHTML = "Please enter text.";
	outputDiv.innerHTML = "Please wait...";
	if (prompt == 'correct') {
		var promptText = "Correct this to standard English:\n\n" + inputText;
	}
	if (prompt == 'rephrase') {
		var promptText = "Rephrase Sentences:\n\n" + inputText;
	}

	const requestData = {
		model: "text-davinci-003",
		prompt: promptText,
		temperature: 0.7,
		max_tokens: 60,
		top_p: 1.0,
		frequency_penalty: 0.0,
		presence_penalty: 0.0
	};

	const requestHeaders = {
		"Content-Type": "application/json",
		"Authorization": "Bearer " + aiet_openaiApiKey
	};

	fetch(aiet_openaiEndpoint, {
			method: "POST",
			headers: requestHeaders,
			body: JSON.stringify(requestData)
		})
		.then(response => response.json())
		.then(data => {
			const correctedText = data.choices[0].text;
			outputDiv.innerHTML = correctedText;
		})
		.catch(error => {
			console.error("Error:", error);
			outputDiv.innerHTML = "Error occurred: " + error;
		});
}

const aiet_button = document.getElementById("aiet_myButton");
const aiet_popup = document.getElementById("aiet_popup");
const aiet_popupText = document.getElementById("aiet_popupText");
const aiet_closeButton = document.getElementById("aiet_closeButton");
const aiet_inputText = document.getElementById("aiet_inputText");

document.body.addEventListener("mouseup", function (event) {
	const selection = window.getSelection();
	const target = event.target;
	if (selection.type === "Range" && target !== aiet_popup && !aiet_popup.contains(target)) {
		const selectedText = selection.toString();
		aiet_button.style.display = "block";
		aiet_button.style.left = event.pageX + "px";
		aiet_button.style.top = event.pageY + "px";
	} else {
		aiet_button.style.display = "none";
	}
});

aiet_button.addEventListener("click", function (event) {
	aiet_button.style.display = "none";
	const selection = window.getSelection();
	const selectedText = selection.toString();
	if (selectedText !== '') {
		aiet_inputText.value = selectedText;
		aiet_popup.style.display = "block";
	}
});

aiet_closeButton.addEventListener("click", function (event) {
	aiet_popup.style.display = "none";
	const outputDiv = document.getElementById("aiet_output");
	outputDiv.innerHTML = '';
	aiet_rephraseSentences.style.opacity = "1";
	aiet_correctGrammar.style.opacity = "1";
});