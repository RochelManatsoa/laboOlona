const dotenv = require('dotenv');
dotenv.config();
const fs = require('fs');
const OpenAI = require('openai');
const pdf = require('pdf-parse');

const apiKey = process.env.OPENAI_API_KEY || process.argv[3];
const jsonlPath = 'output.jsonl';

const openai = new OpenAI({
  apiKey
});

const pdfPath = '/home/mast9834/laboOlona/public/uploads/cv/' + process.argv[2];
const assistantId = 'asst_FVlPdIFoQh6UzFp5qjee1brC'; 

// Fonction pour vérifier le statut du run
const checkRunStatus = async (threadId, runId) => {
  try {
    const runStatus = await openai.beta.threads.runs.retrieve(threadId, runId);
    return runStatus;
  } catch (error) {
    console.error('Error checking run status:', error);
  }
};

// Fonction principale pour extraire le texte, convertir en JSONL, sauvegarder dans un fichier,
// télécharger le fichier, créer un thread, ajouter un message et surveiller le run
const main = async () => {
  try {
    // Crée un nouveau thread
    const thread = await openai.beta.threads.create();
    const threadId = thread.id;
    // console.log('Thread created with ID:', threadId);

    // Ajoute un message pour demander l'analyse du CV
    await openai.beta.threads.messages.create(threadId, {
      role: "user",
      content: "Please analyze the resume and extract key information."
    });

    // Télécharge le fichier PDF au thread
    const fileResponse = await openai.files.create({
      file: fs.createReadStream(pdfPath),
      purpose: 'assistants'
    });

    await openai.beta.threads.messages.create(threadId, {
      role: "user",
      content: "Here is the resume file.",
      attachments: [{
        file_id: fileResponse.id,
        tools: [{ type: "file_search" }]
      }]
    });

    // Crée et exécute un run
    const run = await openai.beta.threads.runs.create(threadId, {
      assistant_id: assistantId
    });

    // console.log('Run created with ID:', run.id);

    // Vérifie périodiquement le statut du run
    let runStatus;
    do {
      await new Promise(resolve => setTimeout(resolve, 5000)); // Attendre 5 secondes avant de vérifier le statut
      runStatus = await checkRunStatus(threadId, run.id);
    //   console.log('Current run status:', runStatus.status);
    } while (runStatus.status !== 'completed');

    // Liste les messages du thread après la complétion du run
    if (runStatus.status === 'completed') {
      const messages = await openai.beta.threads.messages.list(threadId);
      for (const message of messages.data.reverse()) {
        // console.log(`${message.role} > ${message.content[0].text.value}`);
        if (message.role === 'assistant') {
            let jsonResult = message.content[0].text.value;
            console.log(jsonResult);
        }
      }
    } else {
      console.log(runStatus.status);
    }

  } catch (error) {
    console.error('Error uploading file or interacting with OpenAI:', error);
  }
};

main();
