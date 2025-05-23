const dotenv = require('dotenv');
dotenv.config();
const axios = require('axios');
const OpenAI = require('openai');

const apiKey = process.env.OPENAI_API_KEY || process.argv[3];

const openai = new OpenAI({
    apiKey
});

const pdfUrl = process.argv[2];

// Fonction pour générer le rapport de recrutement
async function generateRecruitmentReport(pdfUrl) {
    const prompt = `  
    You are a recruitment assistant responsible for reading and extracting key information from a candidate's PDF resume. Your task is to structure this information in a clear and readable format for a recruitment report.

    Here is the candidate's PDF resume is uploaded :
    ${pdfUrl}

    Your report must be written in French, easy to read, and in a professional tone. Use bullet points to organize the information, and ensure that each section is clearly labeled.

    Respond in JSON format with the following structure:
    
    {
      "Informations personnelles": {
        "Nom complet": "",
        "Coordonnées": {
          "Numéro de téléphone": "",
          "Adresse e-mail": ""
        }
      },
      "Résumé Professionnel": "",
      "Expériences Professionnelles": [
        {
          "Titre du poste": "",
          "Nom de l'entreprise": "",
          "Durée de l'emploi": "",
          "Principales responsabilités": []
        }
      ],
      "Formation": [
        {
          "Diplôme": "",
          "Institution": "",
          "Dates de diplomation": ""
        }
      ],
      "Certifications": [
        {
          "Nom de la certification": "",
          "Institution délivrante": "",
          "Date de délivrance": ""
        }
      ],
      "Outils": [],
      "Langages": [
        {
          "Langue": "",
          "Niveau de maîtrise": ""
        }
      ],
      "Références": [
        {
          "Nom": "",
          "Poste": "",
          "Entreprise": "",
          "Coordonnées": ""
        }
      ],
      "Points forts et points faibles": {
        "Points forts": [],
        "Points faibles": []
      },
      "Autres Informations": []
    }

    Ensure the JSON is valid and well-formed.
    `;

    try {
        const response = await openai.chat.completions.create({
            model: "gpt-4o",
            messages: [{ role: 'user', content: prompt }],
            temperature: 0.7,
            max_tokens: 0,
        });
        const result = response.choices[0].message.content.trim();

        // Valider si le résultat est un JSON valide
        try {
            const jsonResult = JSON.parse(result);
            return jsonResult;
        } catch (jsonError) {
            console.error('Invalid JSON returned:', result);
            return null;
        }
    } catch (error) {
        console.error('Erreur lors de la génération du rapport:', error);
        return null;
    }
}

async function main() {
    const report = await generateRecruitmentReport(pdfUrl);

    if (report) {
        console.log('Rapport JSON généré:', report);
        // Insérez le rapport JSON dans votre base de données ou traitez-le comme nécessaire
    } else {
        console.error('Erreur : Impossible de générer le rapport de recrutement.');
    }
}

main();
