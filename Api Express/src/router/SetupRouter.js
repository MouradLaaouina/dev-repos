import Router from "express"
import {getWithToken} from "../service/axiosService.js"
import { responseToDTO } from "../service/responseService.js"

const setupRouter = Router()

///setup/dictionary/payment_types?sortfield=code&sortorder=ASC&limit=100&page=0&active=1
setupRouter.get("/", async (req,res)=>{
        const token = req.get('DOLAPIKEY')
        const pageNbr = req.query.page
        try{
            let url =`/setup/dictionary/payment_types?sortfield=code&sortorder=ASC&limit=100&page=${pageNbr}&active=1`  
            const response = await getWithToken(url,token)
            res.send({
                    status:"ok",
                    result: responseToDTO(response,null)
                })
        }catch(error){
            console.log(error);
            res.send({
                status:error
            })
        }
})

export default setupRouter
