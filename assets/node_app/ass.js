const dotenv = require('dotenv');
require('dotenv').config();
dotenv.config();
const fs = require('fs');
const OpenAI = require('openai');
const https = require('https');
const axios = require('axios');
const path = require('path');

const apiKey = process.env.OPENAI_API_KEY || process.argv[3];
const candidatId = process.argv[4];

const openai = new OpenAI({
  apiKey
});

const assistantId = 'asst_FVlPdIFoQh6UzFp5qjee1brC'; 

const httpsAgent = new https.Agent({
  rejectUnauthorized: false
});

const fetchPdfText = async (candidatId) => {
  const url = `https://develop.olona-talents.com/api/ocr/${candidatId}`;
  console.log("Requesting URL:", url);
  try {
    const response = await axios.get(url, {
      httpsAgent: httpsAgent
    });
    const pdfText = response.data.text;
    return pdfText;
  } catch (error) {
    console.error('Erreur lors de la récupération et de la lecture du PDF:', error);
    return null;
  }
};

// Fonction pour vérifier le statut du run
const checkRunStatus = async (threadId, runId) => {
  try {
    const runStatus = await openai.beta.threads.runs.retrieve(threadId, runId);
    return runStatus;
  } catch (error) {
    console.error('Error checking run status:', error);
  }
};

// Fonction pour créer un fichier texte
const createTextFile = async (text, outputPath) => {
  fs.writeFileSync(outputPath, text);
};

const main = async () => {
  try {
    console.log('Début du processus...');

    const pdfText = await fetchPdfText(candidatId);
    if (!pdfText) {
      throw new Error('Erreur lors de la récupération du texte PDF');
    }
    console.log('Texte PDF récupéré.');

    // Créez un fichier texte avec le contenu extrait du PDF
    const textFilePath = path.join('/var/www/olonaTalents/laboOlona/public/uploads/cv/', 'resume.txt');
    await createTextFile(pdfText, textFilePath);
    console.log('Fichier texte créé.');

    // Crée un nouveau thread
    const thread = await openai.beta.threads.create();
    const threadId = thread.id;
    console.log('Thread created with ID:', threadId);

    try {
      // Ajoute un message pour demander l'analyse du CV
      await openai.beta.threads.messages.create(threadId, {
        role: 'user',
        content: 'Please analyze the resume and extract key information.'
      });
      console.log('Message pour l\'analyse du CV envoyé.');
    } catch (error) {
      console.error('Error adding message for CV analysis:', error);
      throw error;
    }

    try {
      // Téléchargez le fichier texte (.txt) au thread
      const fileResponse = await openai.files.create({
        file: fs.createReadStream(textFilePath),
        purpose: 'assistants' // or another appropriate purpose depending on your need
      });
      console.log('Fichier texte téléchargé.');

      await openai.beta.threads.messages.create(threadId, {
        role: 'user',
        content: 'Here is the resume file.',
        attachments: [{
          file_id: fileResponse.id,
          tools: [{ type: 'file_search' }] // or another type depending on your use-case
        }]
      });
      console.log('Message avec fichier texte envoyé.');

      await openai.beta.threads.messages.create(threadId, {
        role: 'user',
        content: 'If the document did not yield any direct searchable results. Here is the resume content extracted as text: ' + pdfText,
      });
      console.log('Message avec texte extrait du CV envoyé.');
    } catch (error) {
      console.error('Error handling file upload or messages:', error);
      throw error;
    }

    // Créez et exécutez un run
    const run = await openai.beta.threads.runs.create(threadId, {
      assistant_id: assistantId
    });
    console.log('Run created with ID:', run.id);

    // Vérifiez périodiquement le statut du run
    let runStatus;
    do {
      await new Promise(resolve => setTimeout(resolve, 5000)); // Attendre 5 secondes avant de vérifier le statut
      runStatus = await checkRunStatus(threadId, run.id);
      // console.log('Current run status:', runStatus.status);
    } while (runStatus.status !== 'completed');

    // Listez les messages du thread après la complétion du run
    if (runStatus.status === 'completed') {
      const messages = await openai.beta.threads.messages.list(threadId);
      for (const message of messages.data.reverse()) {
        if (message.role === 'assistant') {
          let jsonResult = message.content[0].text.value;
          console.log(jsonResult);
        }
      }
    } else {
      console.log('Run status:', runStatus.status);
    }

  } catch (error) {
    console.error('Error in the main function:', error);
  }
};

main();