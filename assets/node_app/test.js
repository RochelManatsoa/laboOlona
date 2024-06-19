const dotenv = require('dotenv');
dotenv.config();
const axios = require('axios');
const OpenAI = require('openai');
const FormData = require('form-data');
const fs = require('fs'); 
const pdfParse = require('pdf-parse');

const apiKey = process.env.OPENAI_API_KEY || process.argv[3];

const openai = new OpenAI({
    apiKey
});

const filePath = process.argv[2];

// L'ID de votre assistant
const ASSISTANT_ID = 'asst_z70vOyPxHraPye85wYiv3CmX';

// Le chemin vers le fichier PDF
const pdfPath = '/home/mast9834/laboOlona/public/uploads/cv/' + filePath;

// Créer une fonction pour envoyer le fichier PDF à l'API OpenAI
async function useAssistant() {
  try {
    const form = new FormData();
    form.append('file', fs.createReadStream(pdfPath));
    form.append('purpose', 'assistants');

    // Envoyer le fichier PDF à l'API pour obtenir un file_id
    const uploadResponse = await axios.post('https://api.openai.com/v1/files', form, {
      headers: {
        'Authorization': `Bearer ${apiKey}`,
        ...form.getHeaders()
      }
    });

    const fileId = uploadResponse.data.id;

    // Utiliser l'assistant avec le file_id obtenu
    const response = await axios.post('https://api.openai.com/v1/chat/completions', {
      model: 'gpt-4o',
      messages: [
        {
          role: 'system',
          content: "You are a recruitment assistant responsible for reading and extracting key information from a candidate's PDF resume. Your task is to structure this information in a clear and readable format for a recruitment report."
        },
        {
          role: 'user',
          content: `Please read the resume from the file with ID ${fileId} and generate the report.`
        }
      ],
      temperature: 1,
      top_p: 1
    }, {
      headers: {
        'Authorization': `Bearer ${apiKey}`,
        'Content-Type': 'application/json'
      }
    });
    // Extraire le message et l'usage de la réponse
    const message = response.data.choices[0].message.content;
    const usage = response.data.usage;

    console.log('Message:', message);
    console.log('Usage:', usage);
  } catch (error) {
    console.error('Error:', error.response ? error.response.data : error.message);
  }
}

useAssistant();