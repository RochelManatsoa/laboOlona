const dotenv = require('dotenv');
dotenv.config();
const axios = require('axios');
const OpenAI = require('openai');
const FormData = require('form-data');
const fs = require('fs'); 
const pdf = require('pdf-parse');

const apiKey = process.env.OPENAI_API_KEY || process.argv[3];

const openai = new OpenAI({
    apiKey
});

const pdfPath = '/home/mast9834/laboOlona/public/uploads/cv/' + process.argv[2];

const text = await extractTextFromPdf(pdfPath);
const jsonlContent = convertTextToJsonl(text);


// Fonction pour extraire le texte d'un fichier PDF
const extractTextFromPdf = async (pdfPath) => {
  const dataBuffer = fs.readFileSync(pdfPath);
  const data = await pdf(dataBuffer);
  return data.text;
};

// Fonction pour convertir le texte en format JSONL
const convertTextToJsonl = (text) => {
  const lines = text.split('\n').filter(line => line.trim() !== '');
  const jsonlLines = lines.map(line => JSON.stringify({ text: line }));
  return jsonlLines.join('\n');
};

// Fonction pour télécharger le fichier PDF et obtenir un file_id
async function uploadPDF(jsonlContent) {
    try {
      const form = new FormData();
      form.append('file', fs.createReadStream(jsonlContent));
      form.append('purpose', 'fine-tune');
  
      const uploadResponse = await axios.post('https://api.openai.com/v1/files', form, {
        headers: {
          'Authorization': `Bearer ${apiKey}`,
          ...form.getHeaders()
        }
      });
  
      return uploadResponse.data.id;
    } catch (error) {
      console.error('Error uploading PDF:', error.response ? error.response.data : error.message);
    }
  }
  
  // Appeler la fonction pour télécharger le PDF et obtenir un file_id
  uploadPDF(pdfPath).then(fileId => {
    console.log('File ID:', fileId);
    // Passez à l'étape suivante avec le fileId
  });

  // Fonction pour créer un vector store et attacher le fichier PDF
async function createVectorStore(fileId) {
    try {
      const vectorStoreResponse = await axios.post('https://api.openai.com/v1/vector_stores', {}, {
        headers: {
          'Authorization': `Bearer ${apiKey}`,
          'Content-Type': 'application/json',
          'OpenAI-Beta': 'assistants=v2'
        }
      });
      console.log(vectorStoreResponse.data)
  
      const vectorStoreId = vectorStoreResponse.data.id;
  
      const attachResponse = await axios.post(`https://api.openai.com/v1/vector_stores/${vectorStoreId}/files`, {
        file_id: fileId,
        chunking_strategy: { type: 'auto' }
      }, {
        headers: {
          'Authorization': `Bearer ${apiKey}`,
          'Content-Type': 'application/json',
          'OpenAI-Beta': 'assistants=v2'
        }
      });
  
      return vectorStoreId;
    } catch (error) {
      console.error('Error creating vector store or attaching file:', error.response ? error.response.data : error.message);
    }
  }
  
  // Chaîner les appels pour créer le vector store après avoir obtenu le file_id
  uploadPDF(pdfPath).then(fileId => {
    if (fileId) {
      createVectorStore(fileId).then(vectorStoreId => {
        console.log('Vector Store ID:', vectorStoreId);
        // Passez à l'étape suivante avec le vectorStoreId
      });
    }
  });

  // Fonction pour interagir avec l'assistant en utilisant le vector store
async function useAssistantWithVectorStore(vectorStoreId) {
    try {
      const response = await axios.post('https://api.openai.com/v1/chat/completions', {
        model: 'gpt-4o',
        messages: [
          {
            role: 'assistant',
            content: "You are a recruitment assistant responsible for reading and extracting key information from a candidate's PDF resume. Your task is to structure this information in a clear and readable format for a recruitment report."
          },
          {
            role: 'user',
            content: `run the command.`
          }
        ],
        temperature: 1,
        top_p: 1
      }, {
        headers: {
          'Authorization': `Bearer ${apiKey}`,
          'Content-Type': 'application/json',
          'OpenAI-Beta': 'assistants=v2'
        }
      });
  
      const message = response.data.choices[0].message.content;
      const usage = response.data.usage;
  
      console.log('Message:', message);
      console.log('Usage:', usage);
    } catch (error) {
      console.error('Error:', error.response ? error.response.data : error.message);
    }
  }
  
  // Chaîner les appels pour interagir avec l'assistant après avoir créé le vector store
  uploadPDF(pdfPath).then(fileId => {
    if (fileId) {
      createVectorStore(fileId).then(vectorStoreId => {
        if (vectorStoreId) {
          useAssistantWithVectorStore(vectorStoreId);
        }
      });
    }
  });
