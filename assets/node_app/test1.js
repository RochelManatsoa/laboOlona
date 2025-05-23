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

const pdfPath = '/home/mast9834/laboOlona/public/uploads/cv/' + process.argv[2];

// Fonction pour extraire le texte du PDF
async function extractTextFromPDF(pdfPath) {
  const dataBuffer = fs.readFileSync(pdfPath);
  const data = await pdfParse(dataBuffer);
  return data.text;
}

// Créer une fonction pour envoyer le texte extrait à l'API OpenAI
async function useAssistant(pdfText) {
  try {
    const response = await axios.post('https://api.openai.com/v1/chat/completions', {
      model: 'gpt-4o',
      messages: [
        {
          role: 'system',
          content: "You are a recruitment assistant responsible for reading and extracting key information from a candidate's PDF resume. Your task is to structure this information in a clear and json format for a recruitment report."
        },
        {
          role: 'user',
          content: `Here is the resume text: \n\n${pdfText}`
        }
      ],
      temperature: 1,
      max_tokens: 4096,
      top_p: 1
    }, {
      headers: {
        'Authorization': `Bearer ${apiKey}`,
        'Content-Type': 'application/json'
      }
    });

    // Extraire le message et l'usage de la réponse
    const message = response.data.choices[0].message.content;

    console.log(message);
  } catch (error) {
    console.error('Error:', error.response ? error.response.data : error.message);
  }
}

// Extraire le texte du PDF et utiliser l'assistant
extractTextFromPDF(pdfPath)
  .then(pdfText => useAssistant(pdfText))
  .catch(error => console.error('Error extracting text from PDF:', error));