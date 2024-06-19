const axios = require('axios');
const pdf = require('pdf-parse');
const https = require('https');

const httpsAgent = new https.Agent({  
    rejectUnauthorized: false
});
const url = process.argv[2];

// Fonction pour télécharger et lire le PDF
async function fetchPdfText(url) {
    try {
        const response = await axios.get(url, { 
            responseType: 'arraybuffer',
            httpsAgent: httpsAgent
        });
        const data = await pdf(response.data);
        console.log(data.text)
    } catch (error) {
        console.log('Erreur lors de la récupération et de la lecture du PDF:', error);
        return null;
    }
}
fetchPdfText(url)