const dotenv = require('dotenv');
dotenv.config();
const axios = require('axios');
const pdf = require('pdf-parse');
const https = require('https');
const OpenAI = require('openai');

const apiKey = process.env.OPENAI_API_KEY || process.argv[3];

const openai = new OpenAI({
    apiKey
});

const url = process.argv[2];

// Fonction pour télécharger et lire le PDF
async function fetchPdfText(url) {
    try {
        const response = await axios.get(url, { responseType: 'arraybuffer' });
        const data = await pdf(response.data);
        console.log(data.text)
        return data.text;
    } catch (error) {
        console.log('Erreur lors de la récupération et de la lecture du PDF:', error);
        return null;
    }
}
fetchPdfText(url)