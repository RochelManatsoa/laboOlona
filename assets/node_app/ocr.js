const dotenv = require('dotenv');
dotenv.config();
const fs = require('fs');
const path = require('path');
const { PDFDocument } = require('pdf-lib');
const { createCanvas } = require('canvas');
const Tesseract = require('tesseract.js');
const OpenAI = require('openai');

const apiKey = process.env.OPENAI_API_KEY || process.argv[3];

if (!apiKey) {
  console.error('API key is missing.');
  process.exit(1);
}

const openai = new OpenAI({
  apiKey
});

const pdfPath = path.join('/var/www/olonaTalents/laboOlona/public/uploads/cv/', process.argv[2]);

if (!fs.existsSync(pdfPath)) {
  console.error(`File not found: ${pdfPath}`);
  process.exit(1);
}

const assistantId = 'asst_hRTwdXWSJWUfZyrB7NDFby2E';

if (!assistantId) {
  console.error('Assistant ID is missing.');
  process.exit(1);
}

// Fonction pour vérifier le statut du run
const checkRunStatus = async (threadId, runId) => {
  try {
    const runStatus = await openai.beta.threads.runs.retrieve(threadId, runId);
    return runStatus;
  } catch (error) {
    console.error('Error checking run status:', error);
  }
};

// Fonction pour convertir le PDF en images
async function pdfToImages(pdfBuffer) {
  const pdfDoc = await PDFDocument.load(pdfBuffer);
  const images = [];

  for (let i = 0; i < pdfDoc.getPageCount(); i++) {
    const page = pdfDoc.getPage(i);
    const { width, height } = page.getSize();
    const canvas = createCanvas(width, height);
    const context = canvas.getContext('2d');

    const renderContext = {
      canvasContext: context,
      viewport: {
        width,
        height
      }
    };
    await page.render(renderContext).promise;
    images.push(canvas.toBuffer());
  }

  return images;
}

// Fonction pour effectuer l'OCR sur une image
async function performOCR(imageBuffer) {
  try {
    const result = await Tesseract.recognize(imageBuffer, 'eng', {
      logger: (m) => console.log(m),
    });
    return result.data.text;
  } catch (error) {
    console.error('Error during OCR processing:', error);
    return null;
  }
}

// Fonction principale pour traiter le PDF et effectuer l'OCR
async function main() {
  try {
    const pdfBuffer = fs.readFileSync(pdfPath);
    const images = await pdfToImages(pdfBuffer);

    let ocrText = '';

    for (const image of images) {
      const text = await performOCR(image);
      if (text) {
        ocrText += text + '\n';
      }
    }

    if (!ocrText) {
      console.error('OCR failed to extract text from the PDF.');
      process.exit(1);
    }

    console.log('OCR Text:', ocrText);

    // Crée un nouveau thread
    const thread = await openai.beta.threads.create();
    const threadId = thread.id;
    console.log('Thread created with ID:', threadId);

    // Ajoute un message pour demander l'analyse du texte extrait par OCR
    await openai.beta.threads.messages.create(threadId, {
      role: "user",
      content: `Please analyze the following resume text and extract key information:\n\n${ocrText}`
    });

    // Crée et exécute un run
    const run = await openai.beta.threads.runs.create(threadId, {
      assistant_id: assistantId
    });

    console.log('Run created with ID:', run.id);

    // Vérifie périodiquement le statut du run
    let runStatus;
    do {
      await new Promise(resolve => setTimeout(resolve, 5000)); // Attendre 5 secondes avant de vérifier le statut
      runStatus = await checkRunStatus(threadId, run.id);
      console.log('Current run status:', runStatus.status); // Ajoutez ce log pour vérifier le statut
    } while (runStatus.status !== 'completed' && runStatus.status !== 'failed');

    if (runStatus.status === 'failed') {
      console.error('Run failed.');
      process.exit(1);
    }

    // Liste les messages du thread après la complétion du run
    if (runStatus.status === 'completed') {
      const messages = await openai.beta.threads.messages.list(threadId);
      for (const message of messages.data.reverse()) {
        console.log(`${message.role} > ${message.content[0].text.value}`);
        if (message.role === 'assistant') {
          let jsonResult = message.content[0].text.value;
          console.log(jsonResult);
        }
      }
    } else {
      console.log(runStatus.status);
    }

  } catch (error) {
    console.error('Error processing PDF or interacting with OpenAI:', error);
  }
}

main();
